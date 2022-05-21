<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use App\Document\Trade;
use Doctrine\ODM\MongoDB\DocumentManager;

class TradeService
{
    protected const DEFAULT_TRADE_SORT = ['date.timestamp' => 1];

    private $documentManager;
    private $symbolService;

    public function __construct(DocumentManager $documentManager, SymbolService $symbolService)
    {
        $this->documentManager = $documentManager;
        $this->symbolService = $symbolService;
    }

    public function create(
        Order $order,
        Exchange $exchange,
        Date $date,
        Action $action,
        Money $price,
        Money $fee
    ): void {
        if ($this->documentManager->getRepository(Trade::class)->findOneBy(['exchange' => $exchange, 'order' => $order])) {
            return;
        }

        $trade = Trade::create($order, $exchange, $date, $action, $price, $fee);

        $this->documentManager->persist($trade);
        $this->documentManager->flush();
    }

    public function fromCriteria(array $criteria = [], array $sort = self::DEFAULT_TRADE_SORT): array
    {
        return $this->documentManager->getRepository(Trade::class)->findBy($criteria, $sort);
    }

    public function fromSymbol(string $symbol): array
    {
        return $this->fromCriteria(['order.symbol' => strtoupper($symbol)]);
    }

    public function all(): array
    {
        return $this->fromCriteria();
    }

    public function allSumFees(): Money
    {
        $trades = $this->all();
        $fee = 0;
        foreach ($trades as $trade) {
            $fee += $trade->fee()->convertValueTo('EUR');
        }

        return Money::EUR($fee);
    }

    public function holdings(): array
    {
        $trades = $this->all();
        $result = [];
        foreach ($trades as $trade) {
            if (!array_key_exists($trade->order()->symbol(), $result)) {
                $result[$trade->order()->symbol()] = 0;
            }

            if (Action::ACTION_SELL_TYPE === $trade->action()->value()) {
                $result[$trade->order()->symbol()] -= $trade->order()->amount();
            } else {
                $result[$trade->order()->symbol()] += $trade->order()->amount();
            }
        }

        return $result;
    }

    public function profitFromSymbol(string $symbol): float
    {
        $trades = $this->fromSymbol($symbol);

        $buyMoneySpent = 0;
        $buyAmount = 0;
        $sellMoneyGain = 0;
        $sellAmount = 0;
        foreach ($trades as $trade) {
            if ($trade->action()->isBuy()) {
                $buyMoneySpent += $trade->total()->convertValueTo(Money::USD);
                $buyAmount += $trade->order()->amount();
            }

            if ($trade->action()->isSell()) {
                $sellMoneyGain += $trade->total()->convertValueTo(Money::USD);
                $sellAmount += $trade->order()->amount();
            }

            if ($trade->action()->isStaking()) {
                $buyMoneySpent += 0;
                $buyAmount += $trade->order()->amount();
            }
        }

        return $sellMoneyGain - $buyMoneySpent;
    }

    public function holdingsFromCriteria(array $criteria): float
    {
        $criteria['action.value'] = Action::ACTION_BUY_TYPE;

        $group = [
            '_id' => '$order.symbol',
            'amount' => ['$sum' => '$order.amount'],
        ];

        $bought = $this->tradeAggregate($criteria, $group);

        $criteria['action.value'] = Action::ACTION_SELL_TYPE;

        $sold = $this->tradeAggregate($criteria, $group);

        $calculateHoldings = ($bought[0]['amount'] ?? 0) - ($sold[0]['amount'] ?? 0);

        return max($calculateHoldings, 0);
    }

    public function holdingsFromSymbol(string $symbol): float
    {
        $criteria = [
            'order.symbol' => strtoupper($symbol),
        ];

        return $this->holdingsFromCriteria($criteria);
    }

    public function feesFromCriteria(array $criteria): Money
    {
        $trades = $this->fromCriteria($criteria);
        $fees = 0;

        foreach ($trades as $trade) {
            $fees += $trade->fee()->convertValueTo(Money::USD);
        }

        return Money::USD($fees);
    }

    public function feesFromSymbol(string $symbol): Money
    {
        $criteria = [
            'order.symbol' => strtoupper($symbol),
        ];

        return $this->feesFromCriteria($criteria);
    }

    public function removeFromCriteria(array $criteria)
    {
        $queryBuilder = $this->documentManager->createQueryBuilder(Trade::class)->remove();
        foreach ($criteria as $field => $value) {
            $queryBuilder->field($field)->equals($value);
        }

        $queryBuilder->getQuery()->execute();
    }

    public function calculateProfitFirstInFirstOut(string $symbol, int $year = 2021): Money
    {
        $sellTrades = $this->fromCriteria([
            'order.symbol' => $symbol,
            'action.value' => Action::ACTION_SELL_TYPE,
            'date.year' => $year,
        ]);

        $boughtTrades = $this->fromCriteria([
            'order.symbol' => $symbol,
            'action.value' => Action::ACTION_BUY_TYPE,
            'date.year' => $year,
        ]);

        $totalProfit = 0;
        $boughtArray = [];
        foreach ($boughtTrades as $elementBought) {
            $balance = $this->createNewOrder($elementBought->order()->symbol(), $elementBought->order()->amount());
            $elementBought->memoryBalance($balance);
            $boughtArray[] = $elementBought;
        }

        foreach ($sellTrades as $elementSold) {
            $balance = $this->createNewOrder($elementSold->order()->symbol(), $elementSold->order()->amount());
            $elementSold->memoryBalance($balance);
        }

        foreach ($sellTrades as $sellTrade) {
            $sellAmount = $sellTrade->balance()->amount();

            foreach ($boughtArray as $elementBuy) {
                if (0 === $elementBuy->balance()->amount()) {
                    continue;
                }
                if ($sellAmount <= $elementBuy->balance()->amount()) {
                    $sellTradePrice = $sellTrade->price()->convertValueTo(Money::EUR);
                    $boughtTradePrice = $elementBuy->price()->convertValueTo(Money::EUR);

                    $balanceBuy = $this->createNewOrder($elementBuy->order()->symbol(), ($elementBuy->balance()->amount() - $sellAmount));

                    $elementBuy->memoryBalance($balanceBuy);

                    $profit = ($sellTradePrice - $boughtTradePrice) * $sellAmount;
                    $totalProfit += $profit;

                    $balanceSell = $this->createNewOrder($sellTrade->order()->symbol(), ($sellTrade->balance()->amount() - $sellAmount));

                    $sellTrade->memoryBalance($balanceSell);

                    break;
                } else {
                    $sellAmount = $elementBuy->balance()->amount();
                    $sellTradePrice = $sellTrade->price()->convertValueTo(Money::EUR);

                    $boughtTradePrice = $elementBuy->price()->convertValueTo(Money::EUR);

                    $balanceBuy = $this->createNewOrder($elementBuy->order()->symbol(), 0);

                    $elementBuy->memoryBalance($balanceBuy);
                    $profit = ($sellTradePrice - $boughtTradePrice) * $sellAmount;
                    $totalProfit += $profit;

                    $balanceSell = $this->createNewOrder($sellTrade->order()->symbol(), ($sellTrade->balance()->amount() - $sellAmount));

                    $sellTrade->memoryBalance($balanceSell);

                    $sellAmount = $sellTrade->balance()->amount();
                }
            }
        }

        return Money::EUR($totalProfit);
    }

    public function calculateProfitFirstInFirstOutAll(int $year): Money
    {
        $totalProfit = 0;
        foreach ($this->symbolService->getListOfSymbols() as $symbol) {
            $profit = $this->calculateProfitFirstInFirstOut($symbol, $year);
            $totalProfit += $profit->value();
        }

        return Money::EUR($totalProfit);
    }

    public function totalOfProfit(): Money
    {
        $startYear = (int) $_ENV['START_YEAR'];
        $lastTradeDateYear = $this->lastTradeDate()->year();

        $result = 0;
        for ($i = $startYear; $i <= $lastTradeDateYear; ++$i) {
            $result += $this->calculateProfitFirstInFirstOutAll($i)->value();
        }

        return Money::EUR($result);
    }

    public function totalOfTrades(): int
    {
        return count($this->fromCriteria([
           'action.value' => [
               '$in' => [
                   Action::ACTION_BUY_TYPE,
                   Action::ACTION_SELL_TYPE,
               ],
           ],
       ]));
    }

    public function lastTradeDate(): Date
    {
        $lastTrade = $this->documentManager->createQueryBuilder(Trade::class)
            ->sort('timestamp', 'desc')
            ->getQuery()
            ->getSingleResult();

        return $lastTrade->date();
    }

    private function tradeAggregate(array $criteria, array $group, array $sort = [], array $firstData = []): array
    {
        $collection = $this->documentManager->getDocumentCollection(Trade::class);

        if (!empty($firstData)) {
            $pipeline[] = $firstData;
        }

        if (!empty($criteria)) {
            $pipeline[] = [
                '$match' => $criteria,
            ];
        }

        $pipeline[] = [
            '$group' => $group,
        ];

        if (empty($sort)) {
            $pipeline[] = [
                '$sort' => ['_id' => 1],
            ];
        }

        return iterator_to_array($collection->aggregate($pipeline, ['cursor' => []]));
    }

    private function createNewOrder(string $symbol, float $amount): Order
    {
        return Order::create(
            $symbol,
            $amount
        );
    }
}

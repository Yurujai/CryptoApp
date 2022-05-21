<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use App\Utils\BitvavoExchangeUtils;
use Bitvavo;

class BitvavoExchangeService implements ExchangeInterface
{
    private $tradeService;

    public function __construct(TradeService $tradeService)
    {
        $this->tradeService = $tradeService;
    }

    public function createInstance()
    {
        return $this->instance();
    }

    public function getPriceOfPairAtTime(string $symbol, string $time)
    {
        // TODO: Implement getPriceOfPairAtTime() method.
    }

    public function instance(): Bitvavo
    {
        return new Bitvavo([
            'APIKEY' => BitvavoExchangeUtils::getApiKey(),
            'APISECRET' => BitvavoExchangeUtils::getApiSecret(),
            'ACCESSWINDOW' => 60000,
        ]);
    }

    public function trades(string $symbol)
    {
        $symbol = strtoupper($symbol).'-EUR';

        return $this->instance()->trades($symbol, []);
    }

    public function saveTrades(): void
    {
        foreach ($this->instance()->assets([]) as $asset) {
            if ('DELISTED' !== $asset['depositStatus']) {
                foreach ($this->instance()->trades($asset['symbol'].'-EUR', []) as $trade) {
                    if (!is_array($trade)) {
                        continue;
                    }

                    $this->tradeService->create(
                        $this->createOrder($trade['market'], (float) $trade['amount']),
                        $this->createExchange($trade),
                        $this->createDate($trade),
                        $this->createAction($trade),
                        $this->createMoney((float) $trade['price']),
                        $this->createMoney((float) $trade['fee'])
                    );
                }
            }
        }
    }

    private function createOrder(string $symbol, float $amount): Order
    {
        $symbol = explode('-', $symbol)[0];

        return Order::create($symbol, $amount);
    }

    private function createExchange(array $record): Exchange
    {
        return Exchange::create($record['id'], BitvavoExchangeUtils::getDefaultExchangeName(), $record['orderId']);
    }

    private function createDate(array $record): Date
    {
        return Date::createFromTimestamp($record['timestamp']);
    }

    private function createAction(array $record): Action
    {
        return Action::createFromName($record['side']);
    }

    private function createMoney(float $value): Money
    {
        return Money::EUR($value);
    }
}

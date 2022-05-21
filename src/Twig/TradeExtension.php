<?php

declare(strict_types=1);

namespace App\Twig;

use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Money;
use App\Service\TradeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TradeExtension extends AbstractExtension
{
    protected $tradeService;

    public function __construct(
        TradeService $tradeService
    ) {
        $this->tradeService = $tradeService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('number_of_trades', [$this, 'getNumberOfTrades']),
            new TwigFunction('date_of_last_trade', [$this, 'getLastTradeDate']),
            new TwigFunction('total_profit', [$this, 'getTotalProfit']),
            new TwigFunction('total_fees', [$this, 'getTotalFees']),
        ];
    }

    public function getNumberOfTrades(): int
    {
        return $this->tradeService->totalOfTrades();
    }

    public function getLastTradeDate(): Date
    {
        return $this->tradeService->lastTradeDate();
    }

    public function getTotalProfit(): Money
    {
        return $this->tradeService->totalOfProfit();
    }

    public function getTotalFees(): Money
    {
        return $this->tradeService->allSumFees();
    }
}

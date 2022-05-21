<?php

declare(strict_types=1);

namespace App\Service;

use App\Utils\BinanceExchangeUtils;
use Binance\API;

class BinanceExchangeService implements ExchangeInterface
{
    private $tradeService;

    public function __construct(TradeService $tradeService)
    {
        $this->tradeService = $tradeService;
    }

    public function instance(): API
    {
        return new API(
            BinanceExchangeUtils::getApiKey(),
            BinanceExchangeUtils::getApiSecret()
        );
    }

    public function createInstance(): API
    {
        return new API(
            BinanceExchangeUtils::getApiKey(),
            BinanceExchangeUtils::getApiSecret()
        );
    }

    public function getPriceOfPairAtTime(string $symbol, string $time): array
    {
        return $this->createInstance()->candlesticks($symbol, '5m', 1, $time);
    }
}

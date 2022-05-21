<?php

declare(strict_types=1);

namespace App\Utils;

class BinanceExchangeUtils
{
    public static function getDefaultExchangeName(): string
    {
        return 'binance';
    }

    public static function isEnabled(): bool
    {
        if (!isset($_SERVER['BINANCE_ENABLED'])) {
            throw new \Exception('Var '.'BINANCE_ENABLED'.' not found');
        }

        return 'true' === $_SERVER['BINANCE_ENABLED'];
    }

    public static function getApiKey(): string
    {
        if (!isset($_SERVER['BINANCE_API_KEY'])) {
            throw new \Exception('Var '.'BINANCE_API_KEY'.' not found');
        }

        return $_SERVER['BINANCE_API_KEY'];
    }

    public static function getApiSecret(): string
    {
        if (!isset($_SERVER['BINANCE_API_SECRET'])) {
            throw new \Exception('Var '.'BINANCE_API_SECRET'.' not found');
        }

        return $_SERVER['BINANCE_API_SECRET'];
    }
}

<?php

declare(strict_types=1);

namespace App\Utils;

class GateioExchangeUtils
{
    public static function getDefaultExchangeName(): string
    {
        return 'gateio';
    }

    public static function isEnabled(): bool
    {
        if (!isset($_SERVER['GATEIO_ENABLED'])) {
            throw new \Exception('Var '.'GATEIO_ENABLED'.' not found');
        }

        return 'true' === $_SERVER['GATEIO_ENABLED'];
    }

    public static function getApiKey(): string
    {
        if (!isset($_SERVER['GATEIO_API_KEY'])) {
            throw new \Exception('Var '.'GATEIO_API_KEY'.' not found');
        }

        return $_SERVER['GATEIO_API_KEY'];
    }

    public static function getApiSecret(): string
    {
        if (!isset($_SERVER['GATEIO_API_SECRET'])) {
            throw new \Exception('Var '.'GATEIO_API_SECRET'.' not found');
        }

        return $_SERVER['GATEIO_API_SECRET'];
    }
}

<?php

declare(strict_types=1);

namespace App\Utils;

class KukoinExchangeUtils
{
    public static function getDefaultExchangeName(): string
    {
        return 'kukoin';
    }

    public static function isEnabled(): bool
    {
        if (!isset($_SERVER['KUKOIN_ENABLED'])) {
            throw new \Exception('Var '.'KUKOIN_ENABLED'.' not found');
        }

        return 'true' === $_SERVER['KUKOIN_ENABLED'];
    }

    public static function getApiKey(): string
    {
        if (!isset($_SERVER['KUKOIN_API_KEY'])) {
            throw new \Exception('Var '.'KUKOIN_API_KEY'.' not found');
        }

        return $_SERVER['KUKOIN_API_KEY'];
    }

    public static function getApiSecret(): string
    {
        if (!isset($_SERVER['KUKOIN_API_SECRET'])) {
            throw new \Exception('Var '.'KUKOIN_API_SECRET'.' not found');
        }

        return $_SERVER['KUKOIN_API_SECRET'];
    }

    public static function getPassPhrase(): string
    {
        if (!isset($_SERVER['KUKOIN_PASSPHRASE'])) {
            throw new \Exception('Var '.'KUKOIN_PASSPHRASE'.' not found');
        }

        return $_SERVER['KUKOIN_PASSPHRASE'];
    }
}

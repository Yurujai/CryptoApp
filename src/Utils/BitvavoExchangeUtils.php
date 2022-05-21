<?php

declare(strict_types=1);

namespace App\Utils;

class BitvavoExchangeUtils
{
    public static function getDefaultExchangeName(): string
    {
        return 'bitvavo';
    }

    public static function isEnabled(): bool
    {
        if (!isset($_SERVER['BITVAVO_ENABLED'])) {
            throw new \Exception('Var '.'BITVAVO_ENABLED'.' not found');
        }

        return 'true' === $_SERVER['BITVAVO_ENABLED'];
    }

    public static function getApiKey(): string
    {
        if (!isset($_SERVER['BITVAVO_API_KEY'])) {
            throw new \Exception('Var '.'BITVAVO_API_KEY'.' not found');
        }

        return $_SERVER['BITVAVO_API_KEY'];
    }

    public static function getApiSecret(): string
    {
        if (!isset($_SERVER['BITVAVO_API_SECRET'])) {
            throw new \Exception('Var '.'BITVAVO_API_SECRET'.' not found');
        }

        return $_SERVER['BITVAVO_API_SECRET'];
    }
}

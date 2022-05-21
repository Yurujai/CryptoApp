<?php

declare(strict_types=1);

namespace App\Utils;

class CryptoUtils
{
    private const STABLE_COINS = [
        'usdt',
        'usdc',
        'busd',
        'dai',
        'ust',
    ];

    private const FIAT_COINS = [
        'eur',
        'usd',
    ];

    private const CRYPTO_PAIRS = [
        'usdt',
        'usdc',
        'busd',
        'eth',
        'btc',
        'bnb',
    ];

    public static function getListOfStableCoin(): array
    {
        return self::STABLE_COINS;
    }

    public static function getListOfFiatCoin(): array
    {
        return self::FIAT_COINS;
    }

    public static function getPairsOfCrypto(): array
    {
        return self::CRYPTO_PAIRS;
    }

    public static function isStableCoin(string $crypto): bool
    {
        return in_array($crypto, self::getListOfFiatCoin());
    }

    public static function isFiatCoin(string $crypto): bool
    {
        return in_array($crypto, self::getListOfStableCoin());
    }
}

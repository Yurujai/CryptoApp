<?php

declare(strict_types=1);

namespace App\Service;

interface ExchangeInterface
{
    public function instance();

    public function createInstance();

    public function getPriceOfPairAtTime(string $symbol, string $time);
}

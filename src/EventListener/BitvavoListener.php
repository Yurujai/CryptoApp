<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\BitvavoExchangeService;

class BitvavoListener
{
    private $bitvavoExchangeService;

    public function __construct(BitvavoExchangeService $bitvavoExchangeService)
    {
        $this->bitvavoExchangeService = $bitvavoExchangeService;
    }

    public function updateTrades(): void
    {
        $this->bitvavoExchangeService->saveTrades();
    }
}

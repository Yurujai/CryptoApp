<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\ObjectValue\Date;
use App\Document\Trade;
use Doctrine\ODM\MongoDB\DocumentManager;

class ExchangeService
{
    protected $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getListOfExchanges()
    {
        return $this->documentManager->createQueryBuilder(Trade::class)
            ->distinct('exchange.name')
            ->getQuery()
            ->execute();
    }

    public function getNumberOfTrades(string $exchange): int
    {
        return count($this->documentManager->getRepository(Trade::class)->findBy(['exchange.name' => $exchange]));
    }

    public function getLastTradeDate(string $exchange): Date
    {
        $lastTrade = $this->documentManager->createQueryBuilder(Trade::class)
            ->field('exchange.name')->equals($exchange)
            ->sort('timestamp', 'desc')
            ->getQuery()
            ->getSingleResult();

        return $lastTrade->date();
    }
}

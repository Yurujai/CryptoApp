<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\Trade;
use Doctrine\ODM\MongoDB\DocumentManager;

class SymbolService
{
    protected $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getListOfSymbols()
    {
        return $this->documentManager->createQueryBuilder(Trade::class)
            ->distinct('order.symbol')
            ->getQuery()
            ->execute();
    }
}

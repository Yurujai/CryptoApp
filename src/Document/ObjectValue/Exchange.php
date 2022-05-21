<?php

declare(strict_types=1);

namespace App\Document\ObjectValue;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument() */
final class Exchange
{
    /**
     * @ODM\Field(name="id", type="string")
     */
    private $platformId;

    /**
     * @ODM\Field(name="name", type="string")
     */
    private $platformName;

    /**
     * @ODM\Field(name="transaction", type="string")
     */
    private $transaction;

    private function __construct(string $platformId, string $platformName, string $transaction)
    {
        $this->platformId = $platformId;
        $this->platformName = $platformName;
        $this->transaction = $transaction;
    }

    public function platformId(): string
    {
        return $this->platformId;
    }

    public function platformName(): string
    {
        return $this->platformName;
    }

    public function transaction(): string
    {
        return $this->transaction;
    }

    public static function create(string $platformId, string $platformName, string $transaction): Exchange
    {
        return new self($platformId, $platformName, $transaction);
    }
}

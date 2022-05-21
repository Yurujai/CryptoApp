<?php

declare(strict_types=1);

namespace App\Document\ObjectValue;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument() */
final class Order
{
    /**
     * @ODM\Field(name="symbol", type="string")
     */
    private $symbol;

    /**
     * @ODM\Field(name="amount", type="float")
     */
    private $amount;

    private function __construct(string $symbol, float $amount)
    {
        $this->symbol = $symbol;
        $this->amount = $amount;
    }

    public function symbol(): string
    {
        return $this->symbol;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public static function create(string $symbol, float $amount): Order
    {
        return new self($symbol, $amount);
    }
}

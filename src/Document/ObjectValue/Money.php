<?php

declare(strict_types=1);

namespace App\Document\ObjectValue;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument() */
final class Money
{
    public const USD = 'USD';
    public const EUR = 'EUR';
    public const USDT = 'USDT';

    /**
     * @ODM\Field(name="value", type="float")
     */
    private $value;

    /**
     * @ODM\Field(name="currency", type="string")
     */
    private $currency;

    private function __construct(float $value, string $currency)
    {
        $this->value = round($value, 8);
        $this->currency = $currency;
    }

    public function value(): float
    {
        return $this->value;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public static function USD(float $value): self
    {
        self::validateValue($value);

        return new self($value, self::USD);
    }

    public static function EUR(float $value): self
    {
        self::validateValue($value);

        return new self($value, self::EUR);
    }

    public static function USDT(float $value): self
    {
        self::validateValue($value);

        return new self($value, self::USDT);
    }

    public function convertValueTo(string $currency): float
    {
        if ($currency === $this->currency) {
            return $this->value;
        }

        if (self::EUR === $this->currency && in_array($currency, [self::USD, self::USDT])) {
            return round($this->value * 1.17, 8);
        }

        if (self::EUR !== $this->currency && self::EUR === $currency) {
            return round($this->value / 1.17, 8);
        }

        if (in_array($this->currency, [self::USD, self::USDT]) && in_array($currency, [self::USD, self::USDT])) {
            return $this->value;
        }

        throw new \Exception("Conversion couldn't be done");
    }

    private static function validateValue(float $value): void
    {
//        if ($value < 0) {
//            throw new \Exception('Money cannot be less than 0');
//        }
    }
}

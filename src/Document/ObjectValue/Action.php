<?php

declare(strict_types=1);

namespace App\Document\ObjectValue;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument() */
final class Action
{
    public const ACTION_BUY_TYPE = 0;
    public const ACTION_SELL_TYPE = 1;
    public const ACTION_STAKING_TYPE = 2;

    private const ACTION_NAME_TYPE = [
        0 => 'BUY',
        1 => 'SELL',
        2 => 'STAKING',
    ];

    /**
     * @ODM\Field(name="value", type="int")
     */
    private $value;

    /**
     * @ODM\Field(name="name", type="string")
     */
    private $name;

    private function __construct(int $value, string $name)
    {
        $this->value = $value;
        $this->name = strtoupper($name);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isBuy(): bool
    {
        return self::ACTION_BUY_TYPE === $this->value;
    }

    public function isSell(): bool
    {
        return self::ACTION_SELL_TYPE === $this->value;
    }

    public function isStaking(): bool
    {
        return self::ACTION_STAKING_TYPE === $this->value;
    }

    public static function createFromName(string $name): Action
    {
        $key = array_search(strtoupper($name), self::ACTION_NAME_TYPE);
        if (false !== $key) {
            return new self($key, $name);
        }

        throw new \InvalidArgumentException("Action name $name not found on mapped values");
    }

    public static function createFromValue(int $value): Action
    {
        return new self($value, self::ACTION_NAME_TYPE[$value]);
    }
}

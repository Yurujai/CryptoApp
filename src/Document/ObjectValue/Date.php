<?php

declare(strict_types=1);

namespace App\Document\ObjectValue;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument() */
final class Date
{
    /**
     * @ODM\Field(name="year", type="int")
     */
    private $year;

    /**
     * @ODM\Field(name="month", type="int")
     */
    private $month;

    /**
     * @ODM\Field(name="day", type="int")
     */
    private $day;

    /**
     * @ODM\Field(name="hour", type="int")
     */
    private $hour;

    /**
     * @ODM\Field(name="minute", type="int")
     */
    private $minute;

    /**
     * @ODM\Field(name="second", type="int")
     */
    private $second;

    /**
     * @ODM\Field(name="timestamp", type="int")
     */
    private $timestamp;

    private function __construct(int $year, int $month, int $day, int $hour, int $minute, int $second)
    {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->timestamp = strtotime($this->year.'/'.$this->month.'/'.$this->day.' '.$this->hour.':'.$this->minute.':'.$this->second);
    }

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function day(): int
    {
        return $this->day;
    }

    public function hour(): int
    {
        return $this->hour;
    }

    public function minute(): int
    {
        return $this->minute;
    }

    public function second(): int
    {
        return $this->second;
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public static function create(int $year, int $month, int $day, int $hour, int $minute, int $second): Date
    {
        return new Date($year, $month, $day, $hour, $minute, $second);
    }

    public static function createFromDateTime(\DateTimeInterface $dateTime): Date
    {
        return self::create(
            (int) $dateTime->format('Y'),
            (int) $dateTime->format('m'),
            (int) $dateTime->format('d'),
            (int) $dateTime->format('H'),
            (int) $dateTime->format('i'),
            (int) $dateTime->format('s')
        );
    }

    public static function createFromTimestamp(int $timestamp): Date
    {
        $dateTime = new \DateTime();
        if (strlen((string) $timestamp) > 10) {
            $dateTime->setTimestamp((int) floor($timestamp / 1000));
        } else {
            $dateTime->setTimestamp($timestamp);
        }

        return self::createFromDateTime($dateTime);
    }

    public static function createFromString(string $date): Date
    {
        return self::createFromDateTime(new \DateTimeImmutable($date));
    }

    public static function now(): Date
    {
        return self::createFromDateTime(new \DateTimeImmutable());
    }

    public function toDateTime(): \DateTime
    {
        $dateTime = new \DateTime();
        $dateTime->setDate($this->year(), $this->month(), $this->day());
        $dateTime->setTime($this->hour(), $this->minute(), $this->second());

        return $dateTime;
    }
}

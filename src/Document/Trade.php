<?php

declare(strict_types=1);

namespace App\Document;

use App\Document\ObjectValue\Action;
use App\Document\ObjectValue\Date;
use App\Document\ObjectValue\Exchange;
use App\Document\ObjectValue\Money;
use App\Document\ObjectValue\Order;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;

/**
 * @ODM\Document(repositoryClass="App\Repository\TradeRepository")
 */
class Trade
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\EmbedOne(name="order", targetDocument="App\Document\ObjectValue\Order")
     */
    private $order;

    /**
     * @ODM\EmbedOne(name="exchange", targetDocument="App\Document\ObjectValue\Exchange")
     */
    private $exchange;

    /**
     * @ODM\EmbedOne(name="date", targetDocument="App\Document\ObjectValue\Date")
     */
    private $date;

    /**
     * @ODM\EmbedOne(name="action", targetDocument="App\Document\ObjectValue\Action")
     */
    private $action;

    /**
     * @ODM\EmbedOne(name="price", targetDocument="App\Document\ObjectValue\Money")
     */
    private $price;

    /**
     * @ODM\EmbedOne(name="fee", targetDocument="App\Document\ObjectValue\Money")
     */
    private $fee;

    /**
     * @ODM\EmbedOne(name="total", targetDocument="App\Document\ObjectValue\Money")
     */
    private $total;

    /**
     * @ODM\EmbedOne(name="balance", targetDocument="App\Document\ObjectValue\Order")
     */
    private $balance;

    private function __construct(
        Order $order,
        Exchange $exchange,
        Date $date,
        Action $action,
        Money $price,
        Money $fee
    ) {
        $this->id = new ObjectId();
        $this->order = $order;
        $this->exchange = $exchange;
        $this->date = $date;
        $this->action = $action;
        $this->price = $price;
        $this->fee = $fee;
        $this->total = $this->calculateTotal();
    }

    public static function create(
        Order $order,
        Exchange $exchange,
        Date $date,
        Action $action,
        Money $price,
        Money $fee
    ): Trade {
        return new self($order, $exchange, $date, $action, $price, $fee);
    }

    public function id(): ObjectIdInterface
    {
        return $this->id;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function exchange(): Exchange
    {
        return $this->exchange;
    }

    public function date(): Date
    {
        return $this->date;
    }

    public function action(): Action
    {
        return $this->action;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function fee(): Money
    {
        return $this->fee;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function memoryBalance(Order $balance): void
    {
        $this->balance = $balance;
    }

    public function balance(): Order
    {
        return $this->balance;
    }

    private function calculateTotal(): Money
    {
        $feeCurrency = $this->fee->currency();
        $total = $this->order->amount() * $this->price->value();

        return Money::$feeCurrency($total);
    }
}

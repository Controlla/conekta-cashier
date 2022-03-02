<?php
namespace Controlla\ConektaCashier;

use Conekta\Order as OrderIntent;
use JsonSerializable;
use Controlla\ConektaCashier\Cashier;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Order implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The Conekta OrderIntent instance.
     *
     * @var \Conekta\OrderIntent
     */
    protected $order;

    /**
     * The related customer instance.
     *
     * @var \Controlla\ConektaCashier\Billable
     */
    protected $customer;

    /**
     * Create a new Order instance.
     *
     * @param  \Conekta\Order  $order
     * @return void
     */
    public function __construct(OrderIntent $order)
    {
        $this->order = $order;
    }

    /**
     * Get the total amount that will be paid.
     *
     * @return string
     */
    public function amount()
    {
        return Cashier::formatAmount($this->rawAmount(), $this->order->currency);
    }

    /**
     * Get the raw total amount that will be paid.
     *
     * @return int
     */
    public function rawAmount()
    {
        return $this->order->amount / 100;
    }

    /**
     * Retrieve the related customer for the payment intent if one exists.
     *
     * @return \Controlla\ConektaCashier\Billable|null
     */
    public function customer()
    {
        if ($this->customer) {
            return $this->customer;
        }

        return $this->customer = Cashier::findBillable($this->order->customer_info->customer_id);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this->order;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Dynamically get values from the Stripe object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->order->{$key};
    }
}
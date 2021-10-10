<?php

namespace Controlla\ConektaCashier;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Conekta\PaymentSource as ConektaPaymentMethod;

class PaymentMethod implements Arrayable, Jsonable, JsonSerializable {
    /**
     * The Conekta model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $owner;

    /**
     * The Conekta PaymentSource instance.
     *
     * @var \Conekta\PaymentSource
     */
    protected $paymentMethod;

    /**
     * Create a new PaymentSource instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @param  \Conekta\PaymentSource  $paymentMethod
     * @return void
     *
     * @throws \Controlla\ConektaCashier\Exceptions\InvalidPaymentMethod
     */
    public function __construct($owner, ConektaPaymentMethod $paymentMethod)
    {
        if ($owner->conekta_id !== $paymentMethod->parent_id) {
            throw InvalidPaymentMethod::invalidOwner($paymentMethod, $owner);
        }

        $this->owner = $owner;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Delete the payment method.
     *
     * @return \Conekta\PaymentSource
     */
    public function delete()
    {
        return $this->owner->removePaymentMethod($this->paymentMethod);
    }

    /**
     * Get the Conekta model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function owner()
    {
        return $this->owner;
    }

    /**
     * Get the Conekta PaymentMethod instance.
     *
     * @return \Conekta\PaymentMethod
     */
    public function asConektaPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->order->toArray();
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
     * Dynamically get values from the Conekta object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->paymentMethod->{$key};
    }
}
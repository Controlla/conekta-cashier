<?php

namespace Controlla\ConektaCashier\Exceptions;

use Exception;
use Conekta\PaymentSource as ConektaPaymentMethod;

class InvalidPaymentMethod extends Exception
{
    /**
     * Create a new InvalidPaymentMethod instance.
     *
     * @param  \Conekta\PaymentSource  $paymentMethod
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @return static
     */
    public static function invalidOwner(ConektaPaymentMethod $paymentMethod, $owner)
    {
        return new static(
            "The payment method `{$paymentMethod->id}` does not belong to this customer `$owner->conekta_id`."
        );
    }
}
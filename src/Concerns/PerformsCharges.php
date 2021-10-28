<?php
namespace Controlla\ConektaCashier\Concerns;

use Controlla\ConektaCashier\Order;

trait PerformsCharges
{
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param  int  $amount
     * @param  array  $paymentMethod
     * @param  array  $options
     * @return \Controlla\ConektaCashier\Order
     *
     * @throws \Controlla\ConektaCashier\Exceptions\IncompletePayment
     */
    public function charge($amount, $paymentMethod = null, array $options = [])
    {
        $options = array_merge([
            'currency' => $this->preferredCurrency(),
            "line_items" => [
                [
                "name" => 'Single charge',
                "unit_price" => $amount * 100,
                "quantity" => 1
                ]
            ],
            "charges" => [
                [
                    "payment_method" => $paymentMethod ? ['type' => 'card', 'payment_source_id' => $paymentMethod ] : [ 'type' => 'default']
                ]
            ]
        ], $options);

        if ($this->hasConektaId()) {
            $options['customer_info'] =  [
                'customer_id' => $this->conekta_id
            ];
        }

        $payment = new Order(
            $this->conekta()->charge($options),
        );

        return $payment;
    }
}
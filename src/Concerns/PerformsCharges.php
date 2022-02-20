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
    public function charge($amount, $paymentMethod = null, array $options = [], $monthlyInstallments = null)
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

        if ($monthlyInstallments) {
             $options['charges'][0]['payment_method'] += [
                'monthly_installments' => $monthlyInstallments
            ];
        }

        if ($this->hasConektaId()) {
            $options['customer_info'] =  [
                'customer_id' => $this->conekta_id
            ];
        }

        $order = new Order(
            $this->conekta()->charge($options),
        );

        return $order;
    }

    /**
     * Refund a customer for a charge.
     *
     * @param  string  $paymentIntent
     * @param  array  $options
     * @return \Conekta\Refund
     */
    public function refund($paymentIntent, $amount, array $options = [])
    {
        $options = array_merge([
            'reason' => 'requested_by_client'
        ],
        $amount ? ['amount' => $amount * 100] : []
         ,$options);

        return $this->conekta()->refund($paymentIntent, $options);
    }
}
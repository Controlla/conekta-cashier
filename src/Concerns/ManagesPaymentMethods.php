<?php

namespace Controlla\ConektaCashier\Concerns;

use Illuminate\Support\Collection;
use Controlla\ConektaCashier\PaymentMethod;
use Conekta\PaymentSource as ConektaPaymentMethod;

trait ManagesPaymentMethods
{
    /**
     * Determines if the customer currently has a default payment method.
     *
     * @return bool
     */
    public function hasDefaultPaymentMethod()
    {
        return (bool) $this->card_type;
    }

    /**
     * Determines if the customer currently has at least one payment method of the given type.
     *
     * @return bool
     */
    public function hasPaymentMethod()
    {
        return $this->paymentMethods()->isNotEmpty();
    }

    /**
     * Get a collection of the customer's payment methods of the given type.
     *
     * @return \Illuminate\Support\Collection|\Controlla\ConektaCashier\PaymentMethod[]
     */
    public function paymentMethods()
    {
        if (! $this->hasConektaId()) {
            return new Collection();
        }

        $customer = $this->asConektaCustomer();

        return Collection::make($customer->payment_sources)->map(function ($paymentMethod) {
            return new PaymentMethod($this, $paymentMethod);
        });
    }

    /**
     * Add a payment method to the customer.
     *
     * @param  \Conekta\PaymentMethod|string  $paymentMethod
     * @return \Controlla\ConektaCashier\PaymentMethod
     */
    public function addPaymentMethod($token, $type = 'card')
    {
        $this->assertCustomerExists();

        $customer = $this->asConektaCustomer();

        $conektaPaymentMethod = $customer->createPaymentSource(array(
            'token_id' => $token,
            'type' => $type
        ));

        $this->updateDefaultPaymentMethodFromConekta();

        return new PaymentMethod($this, $conektaPaymentMethod);
    }

    /**
     * Remove a payment method from the customer.
     *
     * @param  \Conekta\PaymentMethod|string  $paymentMethod
     * @return void
     */
    public function removePaymentMethod($paymentMethod)
    {
        $this->assertCustomerExists();

        $conektaPaymentMethod = $this->resolveConektaPaymentMethod($paymentMethod);

        if ($conektaPaymentMethod->parent_id !== $this->conekta_id) {
            return;
        }

        $customer = $this->asConektaCustomer();

        $defaultPaymentMethod = $customer->default_payment_source_id;

        $conektaPaymentMethod->delete();

        // If the payment method was the default payment method, we'll remove it manually...
        if ($conektaPaymentMethod->id === $defaultPaymentMethod) {
            $this->forceFill([
                'card_type' => null,
                'last_four' => null,
            ])->save();
        }
    }

    /**
     * Get the default payment method for the customer.
     *
     * @return \Controlla\ConektaCashier\PaymentMethod|null
     */
    public function defaultPaymentMethod()
    {
        if (! $this->hasConektaId()) {
            return;
        }

        $customer = $this->asConektaCustomer();

        if ($customer->default_payment_source_id) {
            foreach ($customer->payment_sources as $paymentMethod) {
                if ($paymentMethod->id === $customer->default_payment_source_id) {
                    return new PaymentMethod($this, $paymentMethod);
                }
            }
        }
    }

    /**
     * Update customer's default payment method.
     *
     * @param  \Conekta\PaymentMethod|string  $paymentMethod
     * @return \Controlla\ConektaCashier\PaymentMethod
     */
    public function updateDefaultPaymentMethod($paymentMethod)
    {
        $this->assertCustomerExists();
        
        $customer = $this->asConektaCustomer();

        $conektaPaymentMethod = $this->resolveConektaPaymentMethod($paymentMethod);

        // If the customer already has the payment method as their default, we can bail out
        // of the call now. We don't need to keep adding the same payment method to this
        // model's account every single time we go through this specific process call.
        if ($conektaPaymentMethod->id === $customer->default_payment_source_id) {
            return;
        }


        $this->updateConektaCustomer(['default_payment_source_id' => $conektaPaymentMethod->id]);

        // Next we will get the default payment method for this user so we can update the
        // payment method details on the record in the database. This will allow us to
        // show that information on the front-end when updating the payment methods.
        $this->fillPaymentMethodDetails($conektaPaymentMethod);

        $this->save();

        return $conektaPaymentMethod;
    }

    /**
     * Synchronises the customer's default payment method from Conekta back into the database.
     *
     * @return $this
     */
    public function updateDefaultPaymentMethodFromConekta()
    {
        $defaultPaymentMethod = $this->defaultPaymentMethod();

        if ($defaultPaymentMethod && $defaultPaymentMethod instanceof PaymentMethod) {
            $this->fillPaymentMethodDetails(
                $defaultPaymentMethod->asConektaPaymentMethod()
            )->save();
        } else {
            $this->forceFill([
                'card_type' => null,
                'last_four' => null,
            ])->save();
        }

        return $this;
    }

    /**
     * Fills the model's properties with the payment method from Conekta.
     *
     * @param  \Controlla\ConektaCashier\PaymentMethod  $paymentMethod
     * @return $this
     */
    protected function fillPaymentMethodDetails($paymentMethod)
    {
        if ($paymentMethod->type === 'card') {
            $this->card_type = $paymentMethod->brand;
            $this->last_four = $paymentMethod->last4;
        } else {
            $this->card_type = $paymentMethod->type;
            $this->last_four = null;
        }

        return $this;
    }

    /**
     * Deletes the customer's payment methods of the given type.
     *
     * @param  string  $type
     * @return void
     */
    public function deletePaymentMethods($type = 'card')
    {
        $this->paymentMethods($type)->each(function (PaymentMethod $paymentMethod) {
            $paymentMethod->delete();
        });

        $this->updateDefaultPaymentMethodFromConekta();
    }

    /**
     * Find a PaymentMethod by ID.
     *
     * @param  string  $paymentMethod
     * @return \Controlla\ConektaCashier\PaymentMethod|null
     */
    public function findPaymentMethod($paymentMethod)
    {
        $conektaPaymentMethod = null;

        try {
            $conektaPaymentMethod = $this->resolveConektaPaymentMethod($paymentMethod);
        } catch (Exception $exception) {
            //
        }

        return $conektaPaymentMethod ? new PaymentMethod($this, $conektaPaymentMethod) : null;
    }

    /**
     * Resolve a PaymentMethod ID to a Conekta PaymentMethod object.
     *
     * @param  \Conekta\PaymentMethod|string  $paymentMethod
     * @return \Conekta\PaymentMethod
     */
    protected function resolveConektaPaymentMethod($paymentMethod)
    {
        if ($paymentMethod instanceof ConektaPaymentMethod) {
            return $paymentMethod;
        }

        $customer = $this->asConektaCustomer();

        foreach ($customer->payment_sources as $paymentResource) {
            if ($paymentResource->id === $paymentMethod) {
                return $paymentResource;
            }
        }

    }
}
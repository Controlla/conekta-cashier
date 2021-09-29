<?php

namespace Controlla\ConektaCashier\Concerns;

use Controlla\ConektaCashier\Cashier;
use Controlla\ConektaCashier\Exceptions\InvalidCustomer;
use Controlla\ConektaCashier\Exceptions\CustomerAlreadyCreated;

trait ManagesCustomer
{
    /**
     * Retrieve the Conekta customer ID.
     *
     * @return string|null
     */
    public function conektaId()
    {
        return $this->conekta_id;
    }

    /**
     * Determine if the customer has a Conekta customer ID.
     *
     * @return bool
     */
    public function hasConektaId()
    {
        return ! is_null($this->conekta_id);
    }

    /**
     * Determine if the customer has a Conekta customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \Controlla\ConektaCashier\Exceptions\InvalidCustomer
     */
    protected function assertCustomerExists()
    {
        if (! $this->hasConektaId()) {
            throw InvalidCustomer::notYetCreated($this);
        }
    }

    /**
     * Create a Conekta customer for the given model.
     *
     * @param  array  $options
     * @return \Conekta\Customer
     *
     * @throws \Controlla\ConektaCashier\Exceptions\CustomerAlreadyCreated
     */
    public function createAsConektaCustomer(array $options = [])
    {
        if ($this->hasConektaId()) {
            throw CustomerAlreadyCreated::exists($this);
        }

        if (! array_key_exists('name', $options) && $name = $this->name) {
            $options['name'] = $name;
        }

        if (! array_key_exists('email', $options) && $email = $this->email) {
            $options['email'] = $email;
        }

        if (! array_key_exists('phone', $options) && $phone = $this->phone) {
            $options['phone'] = $phone;
        }

        if (! array_key_exists('address', $options) && $address = $this->address) {
            $options['address'] = $address;
        }

        // Here we will create the customer instance on Conekta and store the ID of the
        // user from Conekta. This ID will correspond with the Conekta user instances
        // and allow us to retrieve users from Conekta later when we need to work.
        $customer = $this->conekta()->createCustomer(null, $options);

        $this->conekta_id = $customer->id;

        $this->save();

        return $customer;
    }

    /**
     * Update the underlying Conekta customer information for the model.
     *
     * @param  array  $options
     * @return \Conekta\Customer
     */
    public function updateConektaCustomer(array $options = [])
    {
        return $this->conekta()->updateCustomer(
            $this->conekta_id, $options
        );
    }

    /**
     * Get the Conekta customer instance for the current user or create one.
     *
     * @param  array  $options
     * @return \Conekta\Customer
     */
    public function createOrGetConektaCustomer(array $options = [])
    {
        if ($this->hasConektaId()) {
            return $this->asConektaCustomer();
        }

        return $this->createAsConektaCustomer($options);
    }

    /**
     * Get the Conekta customer for the model.
     *
     * @param  array  $expand
     * @return \Conekta\Customer
     */
    public function asConektaCustomer(array $expand = [])
    {
        $this->assertCustomerExists();

        return $this->conekta()->getCustomer(
            $this->conekta_id
        );
    }

    /**
     * Sync the customer's information to Conekta.
     *
     * @return \Conekta\Customer
     */
    public function syncConektaCustomerDetails()
    {
        return $this->updateConektaCustomer([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);
    }

    /**
     * Get the Conekta SDK client.
     *
     * @return \Conekta\ConektaClient
     */
    public static function conekta()
    {
        return new Cashier();
    }
}
<?php

namespace Controlla\ConektaCashier;

use Conekta\Conekta;
use Conekta\Customer;
use Conekta\Subscription;
use Controlla\ConektaCashier\Cashier;
use Controlla\ConektaCashier\Contracts\Billable as BillableContract;

class Cashier
{
    /**
     * The Cashier library version.
     *
     * @var string
     */
    const VERSION = '5.0.0';

    /**
     * The Conekta API version.
     *
     * @var string
     */
    const CONEKTA_VERSION = '2.0.0';

    /**
     * The billable instance.
     *
     * @var \Controlla\ConektaCashier\Contracts\Billable
     */
    protected $billable;

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Indicates if Cashier migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Indicates if Cashier routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * Indicates if Cashier will mark past due subscriptions as inactive.
     *
     * @var bool
     */
    public static $deactivatePastDue = true;

    /**
     * Indicates if Cashier will automatically calculate taxes using Conekta Tax.
     *
     * @var bool
     */
    public static $calculatesTaxes = false;

    /**
     * The default customer model class name.
     *
     * @var string
     */
    public static $customerModel = 'App\\User';

    /**
     * The subscription model class name.
     *
     * @var string
     */
    // public static $subscriptionModel = Subscription::class;

    /**
     * The subscription item model class name.
     *
     * @var string
     */
    // public static $subscriptionItemModel = SubscriptionItem::class;

    /**
     * Create a new Cashier instance.
     *
     * @param \Controlla\ConektaCashier\Contracts\Billable $billable
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        Conekta::setApiKey($options['api_key'] ?? config('conekta-cashier.secret'));
        Conekta::setApiVersion(static::CONEKTA_VERSION);
    }

    /**
     * Create a new Conekta customer instance.
     *
     * @param string $token
     * @param array  $properties
     *
     * @return \Conekta\Customer
     */
    public function createCustomer($token = null, array $properties = [])
    {
        $payment_sources = !is_null($token) ? [[
            'token_id' => $token,
            'type' => 'card'
        ]] : [];
        
        $customer = Customer::create(
            array_merge(['payment_sources' => $payment_sources], $properties)
        );

        return $customer;
    }

    /**
     * Create a new Conekta customer instance.
     *
     * @param array  $properties
     *
     * @return \Conekta\Customer
     */
    public function updateCustomer($id, array $properties = [])
    {
        $customer = $this->getCustomer($id);

        $customer->update($properties);

        return $customer;
    }

    /**
     * Get the Conekta customer for entity.
     *
     * @return \Conekta\Customer
     */
    public function getCustomer($id = null)
    {
        
        try {
            $customer = Customer::find($id ?: $this->billable->conektaId());
        } catch(ParameterValidationError $e) {
            // No customer;
            $customer = null;
        }
        return $customer;
    }
}
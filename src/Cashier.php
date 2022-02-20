<?php

namespace Controlla\ConektaCashier;

use Money\Money;
use Money\Currency;
use Conekta\Conekta;
use Conekta\Order;
use NumberFormatter;
use Conekta\Customer;
use Conekta\Subscription;
use Money\Currencies\ISOCurrencies;
use Controlla\ConektaCashier\Cashier;
use Money\Formatter\IntlMoneyFormatter;

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
     * @return void
     */
    public function __construct(array $options = [])
    {
        Conekta::setApiKey($options['api_key'] ?? config('conekta-cashier.secret'));
        Conekta::setApiVersion(static::CONEKTA_VERSION);
    }

    /**
     * Set the custom currency formatter.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int  $amount
     * @param  string|null  $currency
     * @param  string|null  $locale
     * @return string
     */
    public static function formatAmount($amount, $currency = null, $locale = null)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount, $currency);
        }

        $money = new Money($amount, new Currency(strtoupper($currency ?? config('conekta-cashier.currency'))));

        $locale = $locale ?? config('cashier.currency_locale');

        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

        return $moneyFormatter->format($money);
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
    public function updateCustomer($conektaId, array $properties = [])
    {
        $customer = $this->getCustomer($conektaId);

        $customer->update($properties);

        return $customer;
    }

    /**
     * Get the Conekta customer for entity.
     *
     * @param  \Controllla\Customer|string|null  $conektaId
     * @return \Controlla\ConektaCashier\Billable|null
     */
    public function getCustomer($conektaId = null)
    {
        
        try {
            $customer = Customer::find($conektaId);
        } catch(ParameterValidationError $e) {
            // No customer;
            $customer = null;
        }
        return $customer;
    }

    /**
     * Get the customer instance by its Conekta ID.
     *
     * @param  \Conekta\Customer|string|null  $conektaId
     * @return \Controlla\ConektaCashier\Billable|null
     */
    public static function findBillable($conektaId)
    {
        $conektaId = $conektaId instanceof Customer ? $conektaId->id : $conektaId;

        return $conektaId ? Customer::find($conektaId) : null;
    }

    /**
    * Make a "one off" charge on the customer for the given amount.
    *
    * @param int   $amount
    * @param array $options
    *
    * @return \Conekta\Order
    */
    public function charge(array $options = [])
    {
        return Order::create($options);
    }

    /**
     * Refund a customer for a charge.
     *
     * @param  string  $paymentIntent
     * @param  array  $options
     */
    public function refund($paymentIntent, array $options = [])
    {
        $order = Order::find($paymentIntent);
        return $order->refund($options);
    }

}
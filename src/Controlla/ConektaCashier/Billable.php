<?php

namespace Controlla\ConektaCashier;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

trait Billable
{
    /**
     * The Conekta API key.
     *
     * @var string
     */
    protected static $conektaKey;

    /**
     * Get the name that should be shown on the entity's invoices.
     *
     * @return string
     */
    public function getBillableName()
    {
        return $this->email;
    }

    /**
     * Write the entity to persistent storage.
     *
     * @return void
     */
    public function saveBillableInstance()
    {
        $this->save();
    }

    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param int   $amount
     * @param array $options
     *
     * @return bool|mixed
     */
    public function charge($amount, array $options = [])
    {
        return (new ConektaGateway($this))->charge($amount, $options);
    }


    

    /**
     * Get a new billing gateway instance for the given plan.
     *
     * @param \Controlla\ConektaCashier\PlanInterface|string|null $plan
     *
     * @return \Controlla\ConektaCashier\ConektaGateway
     */
    public function subscription($plan = null)
    {
        if ($plan instanceof PlanInterface) {
            $plan = $plan->getConektaId();
        }

        return new ConektaGateway($this, $plan);
    }

    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param int   $amount
     * @param array $options
     *
     */
    public function createCard($token, array $options = [])
    {
         (new ConektaGateway($this))->createCard($token, $options);
    }
    

      /**
     * get cards of customer.
     *
     */
    public function getCards()
    {
       return (new ConektaGateway($this))->getCards();
    }

      /**
     * get cards of customer.
     *
     */
    public function getCardDefault()
    {
       return (new ConektaGateway($this))->getCardDefault();
    }
    
    /**
     * Update customer's credit card.
     *
     * @param string $token
     *
     * @return void
     */
    public function updateCard($token)
    {
        return $this->subscription()->updateCard($token);
    }

    /**
     * change default card of customer.
     *
     */
    public function setCardDefault($defaultIdCard)

    {
        
       return (new ConektaGateway($this))->setCardDefault($defaultIdCard);
    }

    /**
     * Determine if the entity is within their trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        if (!is_null($this->getTrialEndDate())) {
            return Carbon::today()->lt($this->getTrialEndDate());
        } else {
            return false;
        }
    }

    /**
     * Determine if the entity is on grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        if (!is_null($endsAt = $this->getSubscriptionEndDate())) {
            return Carbon::today()->lt(Carbon::instance($endsAt));
        } else {
            return false;
        }
    }

    /**
     * Determine if the entity has an active subscription.
     *
     * @return bool
     */
    public function subscribed()
    {
        if ($this->requiresCardUpFront()) {
            return $this->conektaIsActive() || $this->onGracePeriod();
        } else {
            return $this->conektaIsActive() || $this->onTrial() || $this->onGracePeriod();
        }
    }

    /**
     * Determine if the entity's trial has expired.
     *
     * @return bool
     */
    public function expired()
    {
        return !$this->subscribed();
    }

    /**
     * Determine if the entity has a Conekta ID but is no longer active.
     *
     * @return bool
     */
    public function cancelled()
    {
        return $this->readyForBilling() && !$this->conektaIsActive();
    }

    /**
     * Deteremine if the user has ever been subscribed.
     *
     * @return bool
     */
    public function everSubscribed()
    {
        return $this->readyForBilling();
    }

    /**
     * Determine if the entity is on the given plan.
     *
     * @param \Controlla\ConektaCashier\PlanInterface|string $plan
     *
     * @return bool
     */
    public function onPlan($plan)
    {
        if ($plan instanceof PlanInterface) {
            $plan = $plan->getConektaId();
        }

        return $this->conektaIsActive() && $this->subscription()->planId() == $plan;
    }

    /**
     * Determine if billing requires a credit card up front.
     *
     * @return bool
     */
    public function requiresCardUpFront()
    {
        if (isset($this->cardUpFront)) {
            return $this->cardUpFront;
        }

        return true;
    }

    /**
     * Determine if the entity is a Conekta customer.
     *
     * @return bool
     */
    public function readyForBilling()
    {
        return !is_null($this->getConektaId());
    }

    /**
     * Determine if the entity has a current Conekta subscription.
     *
     * @return bool
     */
    public function conektaIsActive()
    {
        return $this->conekta_active;
    }

    /**
     * Set whether the entity has a current Conekta subscription.
     *
     * @param bool $active
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setConektaIsActive($active = true)
    {
        $this->conekta_active = $active;

        return $this;
    }

    /**
     * Set Conekta as inactive on the entity.
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function deactivateConekta()
    {
        $this->setConektaIsActive(false);

        $this->conekta_subscription = null;

        return $this;
    }

    /**
     * Deteremine if the entity has a Conekta customer ID.
     *
     * @return bool
     */
    public function hasConektaId()
    {
        return !is_null($this->conekta_id);
    }

    /**
     * Get the required info to create the customer.
     *
     * @return array
     */
    public function getCustomerInfo()
    {
        return [
            'name' => $this->name,
            'email' => $this->email
        ];
    }

    /**
     * create an array to get default order data for new order 
     *
     * @return array
     */
    public function getDefaultOrder($customer, $amount, $productName, $paymentMethod)
    {
        return  [
            "line_items" => [
                [
                "name" => "Recarga",
                "unit_price" => $amount,
                "quantity" => 1
                ]
            ],
            "currency" => "MXN",
            "customer_info" => [
                "name" => $customer->name,
                "email" => $customer->email,
                "phone" => $this->phone_number
            ],
            "charges" => [
                [
                "payment_method" => [
                    "type" => $paymentMethod,
                    
                ]
                ]
            ]
        ];
    }

    /**
     * Get the Conekta ID for the entity.
     *
     * @return string
     */
    public function getConektaId()
    {
        return $this->conekta_id;
    }

    /**
     * Get the name of the Conekta ID database column.
     *
     * @return string
     */
    public function getConektaIdName()
    {
        return 'conekta_id';
    }

    /**
     * Set the Conekta ID for the entity.
     *
     * @param string $conekta_id
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setConektaId($conekta_id)
    {
        $this->conekta_id = $conekta_id;

        return $this;
    }

    /**
     * Get the current subscription ID.
     *
     * @return string
     */
    public function getConektaSubscription()
    {
        return $this->conekta_subscription;
    }

    /**
     * Set the current subscription ID.
     *
     * @param string $subscription_id
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setConektaSubscription($subscription_id)
    {
        $this->conekta_subscription = $subscription_id;

        return $this;
    }

    /**
     * Get the Conekta plan ID.
     *
     * @return string
     */
    public function getConektaPlan()
    {
        return $this->conekta_plan;
    }

    /**
     * Set the Conekta plan ID.
     *
     * @param string $plan
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setConektaPlan($plan)
    {
        $this->conekta_plan = $plan;

        return $this;
    }

    /**
     * Get the last four digits of the entity's credit card.
     *
     * @return string
     */
    public function getLastFourCardDigits()
    {
        return $this->last_four;
    }

    /**
     * Set the last four digits of the entity's credit card.
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setLastFourCardDigits($digits)
    {
        $this->last_four = $digits;

        return $this;
    }

    /**
     * Get the brand of the entity's credit card.
     *
     * @return string
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set the brand of the entity's credit card.
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setCardType($type)
    {
        $this->card_type = $type;

        return $this;
    }

    /**
     * Get the date on which the trial ends.
     *
     * @return \DateTime
     */
    public function getTrialEndDate()
    {
        return $this->trial_ends_at;
    }

    /**
     * Set the date on which the trial ends.
     *
     * @param \DateTime|null $date
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setTrialEndDate($date)
    {
        $this->trial_ends_at = $date;

        return $this;
    }

    /**
     * Get the subscription end date for the entity.
     *
     * @return \DateTime
     */
    public function getSubscriptionEndDate()
    {
        return $this->subscription_ends_at;
    }

    /**
     * Set the subscription end date for the entity.
     *
     * @param \DateTime|null $date
     *
     * @return \Controlla\ConektaCashier\Contracts\Billable
     */
    public function setSubscriptionEndDate($date)
    {
        $this->subscription_ends_at = $date;

        return $this;
    }

    /**
     * Get the Stripe supported currency used by the entity.
     *
     * @return string
     */
    public function getCurrency()
    {
        return 'mxn';
    }

    /**
     * Get the locale for the currency used by the entity.
     *
     * @return string
     */
    public function getCurrencyLocale()
    {
        return 'es_MX';
    }

    /**
     * Format the given currency for display, without the currency symbol.
     *
     * @param int $amount
     *
     * @return mixed
     */
    public function formatCurrency($amount)
    {
        return number_format($amount / 100, 2);
    }

    /**
     * Add the currency symbol to a given amount.
     *
     * @param string $amount
     *
     * @return string
     */
    public function addCurrencySymbol($amount)
    {
        return '$'.$amount;
    }

    /**
     * Get the Conekta API key.
     *
     * @return string
     */
    public static function getConektaKey()
    {
        return static::$conektaKey ?: Config::get('services.conekta.secret');
    }

    /**
     * Set the Conekta API key.
     *
     * @param string $key
     *
     * @return void
     */
    public static function setConektaKey($key)
    {
        static::$conektaKey = $key;
    }

}

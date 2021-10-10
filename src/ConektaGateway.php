<?php

namespace Controlla\ConektaCashier;

use Carbon\Carbon;
use Conekta\Charge;
use Conekta\Conekta;
use Conekta\Error;
use Conekta\Order;
use Conekta\ParameterValidationError;
use DateTime;
use Controlla\ConektaCashier\Contracts\Billable as BillableContract;
use Controlla\ConektaCashier\Customer;
use InvalidArgumentException;

class ConektaGateway
{
    /**
     * The billable instance.
     *
     * @var \Controlla\ConektaCashier\Contracts\Billable
     */
    protected $billable;

    /**
     * The name of the plan.
     *
     * @var string
     */
    protected $plan;

    /**
     * The trial end date that should be used when updating.
     *
     * @var \Carbon\Carbon
     */
    protected $trialEnd;

    /**
     * Indicates if the trial should be immediately cancelled for the operation.
     *
     * @var bool
     */
    protected $skipTrial = false;


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
         if(!$customer = $this->getConektaCustomer()) {
            if (array_key_exists('payment_method', $options) && $options['payment_method']['type'] == 'oxxo_cash') {
                $options['token'] = null;
            } else {
                if (!array_key_exists('token', $options) && $this->billable->hasConektaId()) {
                    $options['card'] = $this->billable->getConektaId();
                }

                if (!array_key_exists('token', $options)) {
                    throw new InvalidArgumentException('No payment source provided.');
                }
            }

            $customer = $this->createConektaCustomer($options['token'], $this->billable->getCustomerInfo());
            $this->updateLocalConektaData($customer);
        } else if(array_key_exists('token', $options)) {
            if (isset($customer->payment_sources) && is_array($customer->payment_sources) && array_key_exists($customer->payment_sources[0])) {
                $customer->payment_sources[0]->delete();
            }
            
            $customer->createPaymentSource(array(
                'token_id' => $options['token'],
                'type' => 'card'
            ));
            $customer = $this->getConektaCustomer();
            $this->updateLocalConektaData($customer);
        }

        $order = Order::create($this->billable->getDefaultOrder($customer, $amount, $options['product_name'] ?? 'Single charge', $options['payment_method'] ?? null));

        return json_decode($order, true);

    }




    /**
     * set card default
     *
     */
    public function setCardDefault($defaultIdCard)
    {
        $customer = $this->getConektaCustomer();
        return $customer->update(['default_payment_source_id' => $defaultIdCard]);       
    }

    /**
     * Subscribe to the plan for the first time.
     *
     * @param string      $token
     * @param array       $properties
     * @param object|null $customer
     *
     * @return void
     */
    public function create($token, array $properties = [], $customer = null)
    {
        $freshCustomer = false;

        if (!$customer) {
            $customer = $this->createConektaCustomer($token, $properties);

            $freshCustomer = true;
        } elseif (!is_null($token)) {
            $this->updateCard($token);
        }

        $this->billable->setConektaSubscription(
            $customer->updateSubscription($this->buildPayload())->id
        );

        $customer = $this->getConektaCustomer($customer->id);

        if ($freshCustomer && $trialEnd = $this->getTrialEndForCustomer($customer)) {
            $this->billable->setTrialEndDate($trialEnd);
        }

        $this->updateLocalConektaData($customer);
    }

    /**
     * Build the payload for a subscription create / update.
     *
     * @return array
     */
    protected function buildPayload()
    {
        $payload = ['plan' => $this->plan];

        if ($trialEnd = $this->getTrialEndForUpdate()) {
            $payload['trial_end'] = $trialEnd;
        }

        return $payload;
    }

    /**
     * Swap the billable entity to a new plan.
     *
     * @return void
     */
    public function swap()
    {
        $customer = $this->getConektaCustomer();

        // If no specific trial end date has been set, the default behavior should be
        // to maintain the current trial state, whether that is "active" or to run
        // the swap out with the exact number of days left on this current plan.
        if (is_null($this->trialEnd)) {
            $this->maintainTrial();
        }

        return $this->create(null, [], $customer);
    }

    /**
     * Resubscribe a customer to a given plan.
     *
     * @param string $token
     *
     * @return void
     */
    public function resume($token = null)
    {
        $this->skipTrial()->create($token, [], $this->getConektaCustomer());

        $this->billable->setTrialEndDate(null)->saveBillableInstance();
    }

    /**
     * Cancel the billable entity's subscription.
     *
     * @return void
     */
    public function cancel($atPeriodEnd = true)
    {
        $customer = $this->getConektaCustomer();

        if ($customer->subscription) {
            if ($atPeriodEnd) {
                $this->billable->setSubscriptionEndDate(
                    Carbon::createFromTimestamp($this->getSubscriptionEndTimestamp($customer))
                );
            }

            $customer->cancelSubscription();
        }

        if ($atPeriodEnd) {
            $this->billable->setConektaIsActive(false)->saveBillableInstance();
        } else {
            $this->billable->setSubscriptionEndDate(Carbon::now());

            $this->billable->deactivateConekta()->saveBillableInstance();
        }
    }

    /**
     * Extend a subscription trial end datetime.
     *
     * @param \DateTime $trialEnd
     *
     * @return void
     */
    public function extendTrial(DateTime $trialEnd)
    {
        $customer = $this->getConektaCustomer();

        if ($customer->subscription) {
            $customer->updateSubscription(['trial_end' => $trialEnd->format(DateTime::ISO8601)]);

            $this->billable->setTrialEndDate($trialEnd)->saveBillableInstance();
        }
    }

    /**
     * Cancel the billable entity's subscription at the end of the period.
     *
     * @return void
     */
    public function cancelAtEndOfPeriod()
    {
        return $this->cancel(true);
    }

    /**
     * Cancel the billable entity's subscription immediately.
     *
     * @return void
     */
    public function cancelNow()
    {
        return $this->cancel(false);
    }

    /**
     * Get the subscription end timestamp for the customer.
     *
     * @param \Conekta\Customer $customer
     *
     * @return int
     */
    protected function getSubscriptionEndTimestamp($customer)
    {
        if (!is_null($customer->subscription->trial_end) && $customer->subscription->trial_end > time()) {
            return $customer->subscription->trial_end;
        } else {
            return $customer->subscription->billing_cycle_end;
        }
    }

    /**
     * Get the current subscription period's end date.
     *
     * @return \Carbon\Carbon
     */
    public function getSubscriptionEndDate()
    {
        $customer = $this->getConektaCustomer();

        return Carbon::createFromTimestamp($this->getSubscriptionEndTimestamp($customer));
    }

    /**
     * Update the credit card attached to the entity.
     *
     * @param string $token
     *
     * @return void
     */
    public function updateCard($token)
    {
        $customer = $this->getConektaCustomer();

        $card = $customer->createCard(['token' => $token]);

        $customer->update(['default_payment_source_id' => $card->id]);

        if ($customer->subscription) {
            $customer->updateSubscription(['card' => $card->id]);

            $this->billable
                    ->setLastFourCardDigits($this->getLastFourCardDigits($customer))
                    ->setCardType($this->getCardType($customer))
                    ->saveBillableInstance();
        }

        return $card;
    }

    /**
     * Get the plan ID for the billable entity.
     *
     * @return string
     */
    public function planId()
    {
        $customer = $this->getConektaCustomer();

        if (isset($customer->subscription)) {
            return $customer->subscription->plan_id;
        }
    }

    /**
     * Update the local Conekta data in storage.
     *
     * @param \Conekta\Customer $customer
     * @param string|null       $plan
     *
     * @return void
     */
    public function updateLocalConektaData($customer, $plan = null)
    {
        $this->billable
                ->setConektaId($customer->id)
                ->setConektaPlan($plan ?: $this->plan)
                ->setLastFourCardDigits($this->getLastFourCardDigits($customer))
                ->setCardType($this->getCardType($customer))
                ->setConektaIsActive(true)
                ->setSubscriptionEndDate(null)
                ->saveBillableInstance();
    }

    /**
     * Create a new Conekta customer instance.
     *
     * @param string $token
     * @param array  $properties
     *
     * @return \Conekta\Customer
     */
    public function createConektaCustomer($token = null, array $properties = [])
    {
        $payment_sources = !is_null($token) ? [[
            'token_id' => $token,
            'type' => 'card'
        ]] : [];
        
        $customer = Customer::create(
            array_merge(['payment_sources' => $payment_sources], $properties)
        );

        return $this->getConektaCustomer($customer->id);
    }


    /**
     * Get the last four credit card digits for a customer.
     *
     * @param \Conekta\Customer $customer
     *
     * @return string
     */
    protected function getLastFourCardDigits($customer)
    {
        if (empty($customer->payment_sources[0])) {
            return;
        }

        if ($customer->default_payment_source_id) {
            foreach ($customer->payment_sources as $card) {
                if ($card->id == $customer->default_payment_source_id) {
                    return $card->last4;
                }
            }

            return;
        }

        return $customer->payment_sources[0]->last4;
    }

    /**
     * Get the last four credit card digits for a customer.
     *
     * @param \Conekta\Customer $customer
     *
     * @return string
     */
    protected function getCardType($customer)
    {
        if (empty($customer->payment_sources[0])) {
            return;
        }

        if ($customer->default_payment_source_id) {
            foreach ($customer->payment_sources as $card) {
                if ($card->id == $customer->default_payment_source_id) {
                    return $card->brand;
                }
            }

            return;
        }

        return $customer->payment_sources[0]->brand;
    }

    /**
     * Indicate that no trial should be enforced on the operation.
     *
     * @return \Controlla\ConektaCashier\ConektaGateway
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Specify the ending date of the trial.
     *
     * @param \DateTime $trialEnd
     *
     * @return \Controlla\ConektaCashier\ConektaGateway
     */
    public function trialFor(DateTime $trialEnd)
    {
        $this->trialEnd = $trialEnd;

        return $this;
    }

    /**
     * Get the current trial end date for subscription change.
     *
     * @return \DateTime
     */
    public function getTrialFor()
    {
        return $this->trialEnd;
    }

    /**
     * Maintain the days left of the current trial (if applicable).
     *
     * @return \Controlla\ConektaCashier\ConektaGateway
     */
    public function maintainTrial()
    {
        if ($this->billable->readyForBilling()) {
            if (!is_null($trialEnd = $this->getTrialEndForCustomer($this->getConektaCustomer()))) {
                $this->calculateRemainingTrialDays($trialEnd);
            } else {
                $this->skipTrial();
            }
        }

        return $this;
    }

    /**
     * Get the trial end timestamp for a Conekta subscription update.
     *
     * @return int
     */
    protected function getTrialEndForUpdate()
    {
        if ($this->skipTrial) {
            return Carbon::now()->toIso8601String();
        }

        return $this->trialEnd ? $this->trialEnd->toIso8601String() : null;
    }

    /**
     * Get the trial end date for the customer's subscription.
     *
     * @param object $customer
     *
     * @return \Carbon\Carbon|null
     */
    public function getTrialEndForCustomer($customer)
    {
        if (isset($customer->subscription) && $customer->subscription->status == 'in_trial' && isset($customer->subscription->trial_end)) {
            return Carbon::createFromTimestamp($customer->subscription->trial_end);
        }
    }

    /**
     * Calculate the remaining trial days based on the current trial end.
     *
     * @param \Carbon\Carbon $trialEnd
     *
     * @return void
     */
    protected function calculateRemainingTrialDays($trialEnd)
    {
        // If there is still trial left on the current plan, we'll maintain that amount of
        // time on the new plan. If there is no time line_itemsft on the trial we will force it
        // to skip any trials on this new plan, as this is the most expected actions.
        $diff = Carbon::now()->diffInHours($trialEnd);

        return $diff > 0 ? $this->trialFor(Carbon::now()->addHours($diff)) : $this->skipTrial();
    }

}

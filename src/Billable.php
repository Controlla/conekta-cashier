<?php

namespace Controlla\ConektaCashier;

use Controlla\ConektaCashier\Concerns\ManagesCustomer;
use Controlla\ConektaCashier\Concerns\PerformsCharges;
use Controlla\ConektaCashier\Concerns\ManagesPaymentMethods;

trait Billable
{
    use ManagesCustomer;
    use PerformsCharges;
    use ManagesPaymentMethods;

    // /**
    //  * The Conekta API key.
    //  *
    //  * @var string
    //  */
    // protected static $conektaKey;

    // /**
    //  * Get the name that should be shown on the entity's invoices.
    //  *
    //  * @return string
    //  */
    // public function getBillableName()
    // {
    //     return $this->email;
    // }

    // /**
    //  * Write the entity to persistent storage.
    //  *
    //  * @return void
    //  */
    // public function saveBillableInstance()
    // {
    //     $this->save();
    // }
    
    // /**
    //  * Get a new billing gateway instance for the given plan.
    //  *
    //  * @param \Controlla\ConektaCashier\PlanInterface|string|null $plan
    //  *
    //  * @return \Controlla\ConektaCashier\ConektaGateway
    //  */
    // public function subscription($plan = null)
    // {
    //     if ($plan instanceof PlanInterface) {
    //         $plan = $plan->getConektaId();
    //     }

    //     return new ConektaGateway($this, $plan);
    // }

    // /**
    //  * Make a "one off" charge on the customer for the given amount.
    //  *
    //  * @param int   $amount
    //  * @param array $options
    //  *
    //  */
    // public function createCard($token, array $options = [])
    // {
    //     return (new ConektaGateway($this))->createCard($token, $options);
    // }
    

    //   /**
    //  * get cards of customer.
    //  *
    //  */
    // public function getCards()
    // {
    //    return (new ConektaGateway($this))->getCards();
    // }

    //   /**
    //  * get cards of customer.
    //  *
    //  */
    // public function getCardDefault()
    // {
    //    return (new ConektaGateway($this))->getCardDefault();
    // }
    
    // /**
    //  * Update customer's credit card.
    //  *
    //  * @param string $token
    //  *
    //  * @return void
    //  */
    // public function updateCard($token)
    // {
    //     return $this->subscription()->updateCard($token);
    // }

    // /**
    //  * change default card of customer.
    //  *
    //  */
    // public function setCardDefault($defaultIdCard)
    // {
        
    //    return (new ConektaGateway($this))->setCardDefault($defaultIdCard);
    // }

    // /**
    //  * delete customer's credit card.
    //  *
    //  * @param string $paymentSourceId
    //  *
    //  * @return void
    //  */
    // public function deleteCard($paymentSourceId)
    // {
    //     return (new ConektaGateway($this))->deletePaymentSourceById($paymentSourceId);
    // }

    // /**
    //  * Determine if the entity is within their trial period.
    //  *
    //  * @return bool
    //  */
    // public function onTrial()
    // {
    //     if (!is_null($this->getTrialEndDate())) {
    //         return Carbon::today()->lt($this->getTrialEndDate());
    //     } else {
    //         return false;
    //     }
    // }

    // /**
    //  * Determine if the entity is on grace period after cancellation.
    //  *
    //  * @return bool
    //  */
    // public function onGracePeriod()
    // {
    //     if (!is_null($endsAt = $this->getSubscriptionEndDate())) {
    //         return Carbon::today()->lt(Carbon::instance($endsAt));
    //     } else {
    //         return false;
    //     }
    // }

    // /**
    //  * Determine if the entity has an active subscription.
    //  *
    //  * @return bool
    //  */
    // public function subscribed()
    // {
    //     if ($this->requiresCardUpFront()) {
    //         return $this->conektaIsActive() || $this->onGracePeriod();
    //     } else {
    //         return $this->conektaIsActive() || $this->onTrial() || $this->onGracePeriod();
    //     }
    // }

    // /**
    //  * Determine if the entity's trial has expired.
    //  *
    //  * @return bool
    //  */
    // public function expired()
    // {
    //     return !$this->subscribed();
    // }

    // /**
    //  * Determine if the entity has a Conekta ID but is no longer active.
    //  *
    //  * @return bool
    //  */
    // public function cancelled()
    // {
    //     return $this->readyForBilling() && !$this->conektaIsActive();
    // }

    // /**
    //  * Deteremine if the user has ever been subscribed.
    //  *
    //  * @return bool
    //  */
    // public function everSubscribed()
    // {
    //     return $this->readyForBilling();
    // }

    // /**
    //  * Determine if the entity is on the given plan.
    //  *
    //  * @param \Controlla\ConektaCashier\PlanInterface|string $plan
    //  *
    //  * @return bool
    //  */
    // public function onPlan($plan)
    // {
    //     if ($plan instanceof PlanInterface) {
    //         $plan = $plan->getConektaId();
    //     }

    //     return $this->conektaIsActive() && $this->subscription()->planId() == $plan;
    // }

    // /**
    //  * Determine if billing requires a credit card up front.
    //  *
    //  * @return bool
    //  */
    // public function requiresCardUpFront()
    // {
    //     if (isset($this->cardUpFront)) {
    //         return $this->cardUpFront;
    //     }

    //     return true;
    // }

    // /**
    //  * Determine if the entity is a Conekta customer.
    //  *
    //  * @return bool
    //  */
    // public function readyForBilling()
    // {
    //     return !is_null($this->conektaId());
    // }

    // /**
    //  * Determine if the entity has a current Conekta subscription.
    //  *
    //  * @return bool
    //  */
    // public function conektaIsActive()
    // {
    //     return $this->conekta_active;
    // }

    // /**
    //  * Set whether the entity has a current Conekta subscription.
    //  *
    //  * @param bool $active
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setConektaIsActive($active = true)
    // {
    //     $this->conekta_active = $active;

    //     return $this;
    // }

    // /**
    //  * Set Conekta as inactive on the entity.
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function deactivateConekta()
    // {
    //     $this->setConektaIsActive(false);

    //     $this->conekta_subscription = null;

    //     return $this;
    // }

    // /**
    //  * create an array to get default order data for new order 
    //  *
    //  * @return array
    //  */
    // public function getDefaultOrder($customer, $amount, $productName, $paymentMethod)
    // {
    //     return  [
    //         "line_items" => [
    //             [
    //             "name" => $productName,
    //             "unit_price" => $amount,
    //             "quantity" => 1
    //             ]
    //         ],
    //         "currency" => "MXN",
    //         "customer_info" => [
    //             "customer_id" => $customer->id,
    //             "name" => $customer->name,
    //             "email" => $customer->email,
    //             "phone" => $this->phone_number
    //         ],
    //         "charges" => [
    //             [
    //                 "payment_method" => $paymentMethod
    //             ]
    //         ]
    //     ];
    // }

    // /**
    //  * Get the current subscription ID.
    //  *
    //  * @return string
    //  */
    // public function getConektaSubscription()
    // {
    //     return $this->conekta_subscription;
    // }

    // /**
    //  * Set the current subscription ID.
    //  *
    //  * @param string $subscription_id
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setConektaSubscription($subscription_id)
    // {
    //     $this->conekta_subscription = $subscription_id;

    //     return $this;
    // }

    // /**
    //  * Get the Conekta plan ID.
    //  *
    //  * @return string
    //  */
    // public function getConektaPlan()
    // {
    //     return $this->conekta_plan;
    // }

    // /**
    //  * Set the Conekta plan ID.
    //  *
    //  * @param string $plan
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setConektaPlan($plan)
    // {
    //     $this->conekta_plan = $plan;

    //     return $this;
    // }

    // /**
    //  * Get the last four digits of the entity's credit card.
    //  *
    //  * @return string
    //  */
    // public function getLastFourCardDigits()
    // {
    //     return $this->last_four;
    // }

    // /**
    //  * Set the last four digits of the entity's credit card.
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setLastFourCardDigits($digits)
    // {
    //     $this->last_four = $digits;

    //     return $this;
    // }

    // /**
    //  * Get the brand of the entity's credit card.
    //  *
    //  * @return string
    //  */
    // public function getCardType()
    // {
    //     return $this->card_type;
    // }

    // /**
    //  * Set the brand of the entity's credit card.
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setCardType($type)
    // {
    //     $this->card_type = $type;

    //     return $this;
    // }

    // /**
    //  * Get the date on which the trial ends.
    //  *
    //  * @return \DateTime
    //  */
    // public function getTrialEndDate()
    // {
    //     return $this->trial_ends_at;
    // }

    // /**
    //  * Set the date on which the trial ends.
    //  *
    //  * @param \DateTime|null $date
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setTrialEndDate($date)
    // {
    //     $this->trial_ends_at = $date;

    //     return $this;
    // }

    // /**
    //  * Get the subscription end date for the entity.
    //  *
    //  * @return \DateTime
    //  */
    // public function getSubscriptionEndDate()
    // {
    //     return $this->subscription_ends_at;
    // }

    // /**
    //  * Set the subscription end date for the entity.
    //  *
    //  * @param \DateTime|null $date
    //  *
    //  * @return \Controlla\ConektaCashier\Contracts\Billable
    //  */
    // public function setSubscriptionEndDate($date)
    // {
    //     $this->subscription_ends_at = $date;

    //     return $this;
    // }

}

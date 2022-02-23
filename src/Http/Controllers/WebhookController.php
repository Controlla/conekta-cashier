<?php

namespace Controlla\ConektaCashier\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Request;
use Controlla\ConektaCashier\Events\WebhookHandled;
use Controlla\ConektaCashier\Events\WebhookReceived;

class WebhookController extends Controller
{
    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            $response = $this->{$method}($payload);

            WebhookHandled::dispatch($payload);

            return $response;
        }

        return $this->missingMethod($payload);
    }

    /** 
     * Handle webhook ping.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleWebhookPing(array $payload)
    {
        return $this->successMethod();
    }

    /**
     * Handle customer updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerUpdated(array $payload)
    {
        if ($user = $this->getUserByConektaId($payload['data']['object']['customer_id'])) {
            $user->updateDefaultPaymentMethodFromConekta();
        }

        return $this->successMethod();
    }

    /**
     * Handle deleted customer.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerDeleted(array $payload)
    {
        if ($user = $this->getUserByConektaId($payload['data']['object']['customer_id'])) {
            // $user->subscriptions->each(function (Subscription $subscription) {
            //     $subscription->skipTrial()->markAsCanceled();
            // });

            $user->forceFill([
                'conekta_id' => null,
                'trial_ends_at' => null,
                'card_type' => null,
                'last_four' => null,
            ])->save();
        }

        return $this->successMethod();
    }

    /**
     * Get the customer instance by Stripe ID.
     *
     * @param  string|null  $conektaId
     * @return \Laravel\Cashier\Billable|null
     */
    protected function getUserByConektaId($conektaId)
    {
        return Cashier::findBillable($conektaId);
    }

    /**
     * Handle successful calls on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function successMethod($parameters = [])
    {
        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function missingMethod($parameters = [])
    {
        return new Response;
    }
}
<?php

namespace Controlla\ConektaCashier;

use Conekta\Conekta;
use Conekta\Event;
use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle a Conekta webhook call.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook()
    {
        $payload = $this->getJsonPayload();

        if (!$this->eventExistsOnConekta($payload['id'])) {
            return $this->missingConektaEvent($payload);
        }

        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        if (method_exists($this, $method)) {
            return $this->{$method}($payload);
        } else {
            return $this->missingMethod();
        }
    }

    /**
     * Verify with Conekta that the event is genuine.
     *
     * @param string $id
     *
     * @return bool
     */
    protected function eventExistsOnConekta($id)
    {
        try {
            Conekta::setApiKey(Config::get('services.conekta.secret'));

            return !is_null(Event::where(['id' => $id]));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Handle a failed payment from a Conekta subscription.
     *
     * @param array $payload
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleSubscriptionPaymentFailed(array $payload)
    {
        $billable = $this->getBillable($payload['data']['object']['customer_id']);

        if ($billable) {
            $billable->subscription()->cancel();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Get the billable entity instance by Conekta ID.
     *
     * @param string $conektaId
     *
     * @return \Controlla\ConektaCashier\BillableInterface
     */
    protected function getBillable($conektaId)
    {
        return App::make('Controlla\ConektaCashier\BillableRepositoryInterface')->find($conektaId);
    }

    /**
     * Get the billable entity instance by Payload object.
     *
     * @param array $payload
     *
     * @return \Controlla\ConektaCashier\BillableInterface
     */
    protected function getBillableFromPayload($payload)
    {
        return $this->getBillable($payload['data']['object']['customer_info']['customer_id']);
    }

    public function FunctionName(Type $var = null)
    {
        # code...
    }

    /**
     * Get the JSON payload for the request.
     *
     * @return array
     */
    protected function getJsonPayload()
    {
        return (array) json_decode(Request::getContent(), true);
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param array $parameters
     *
     * @return mixed
     */
    public function missingMethod($parameters = [])
    {
        return new Response("", 200);
    }

    /**
     * Handle calls to missing Conekta's events.
     *
     * @param array $parameters
     *
     * @return mixed
     */
    public function missingConektaEvent($parameters = [])
    {
        return new Response("", 404);
    }
}

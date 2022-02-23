# Laravel Conekta Cashier

<p align="center">
<a href="https://github.com/Controlla/conekta-cashier/actions"><img src="https://github.com/controlla/cashier/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/Controlla/conekta-cashier"><img src="https://img.shields.io/packagist/dt/controlla/conekta-cashier" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/Controlla/conekta-cashier"><img src="https://img.shields.io/packagist/v/controlla/conekta-cashier" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/Controlla/conekta-cashier"><img src="https://img.shields.io/packagist/l/controlla/conekta-cashier" alt="License"></a>
</p>

Port of Conekta [Laravel Cashier](https://github.com/laravel/cashier) to Conekta

Please note the latest version of Laravel Cashier supports Laravel 5+, if you are looking for the Laravel 4 implementation see the [1.0](https://github.com/dinkbit/conekta-cashier/tree/1.0) branch.

___

# Conekta Cashier

- [Introduction](#introduction)
- [Configuration](#configuration)
- [Subscribing To A Plan](#subscribing-to-a-plan)
- [Single Charges](#single-charges)
- [Swapping Subscriptions](#swapping-subscriptions)
- [Cancelling A Subscription](#cancelling-a-subscription)
- [Resuming A Subscription](#resuming-a-subscription)
- [Checking Subscription Status](#checking-subscription-status)
- [Handling Conekta Webhooks](#handling-conekta-webhooks)
- [Testing](#testing)

<a name="introduction"></a>
## Introduction

Conekta Cashier provides an expressive, fluent interface to [Conekta's](https://conekta.com) subscription billing services. It handles almost all of the boilerplate subscription billing code you are dreading writing. In addition to basic subscription management, Cashier can handle coupons, swapping subscription, subscription "quantities", cancellation grace periods, and even generate invoice PDFs.

<a name="configuration"></a>
## Configuration

#### Composer

First, add the Controlla Cashier package to your `composer.json` file:

	"controlla/conekta-cashier": "~2.0"

#### Service Provider

Next, register the `Controlla\ConektaCashier\CashierServiceProvider` in your `app` configuration file.

#### Migration

Before using Cashier, we'll need to add several columns to your database. Don't worry, you can use the `conekta-cashier:table` Artisan command to create a migration to add the necessary column. For example, to add the column to the users table use `php artisan conekta-cashier:table users`. Once the migration has been created, simply run the `migrate` command.

#### Model Setup

Next, add the `Billable` trait and appropriate date mutators to your model definition:

	use Dinkbit\ConektaCashier\Billable;
	use Dinkbit\ConektaCashier\Contracts\Billable as BillableContract;

	class User extends Eloquent implements BillableContract {

		use Billable;

		protected $dates = ['trial_ends_at', 'subscription_ends_at'];

	}

#### Conekta Key

Finally, set your Conekta key in your `services.php` config file:

	'conekta' => [
		'model'  => 'User',
		'secret' => env('CONEKTA_API_SECRET'),
	],

Alternatively you can store it in one of your bootstrap files or service providers, such as the `AppServiceProvider`:

	User::setConektaKey('conekta-key');

## Subscribing To A Plan

Once you have a model instance, you can easily subscribe that user to a given Conekta plan:

	$user = User::find(1);

	$user->subscription('monthly')->create($creditCardToken);

You can also extend a subscription trial.

	$subscription = $user->subscription('monthly')->create($creditCardToken);

	$user->extendTrial(Carbon::now()->addMonth());

The `subscription` method will automatically create the Conekta subscription, as well as update your database with Conekta customer ID and other relevant billing information. If your plan has a trial configured in Conekta, the trial end date will also automatically be set on the user record.

If your plan has a trial period that is **not** configured in Conekta, you must set the trial end date manually after subscribing:

	$user->trial_ends_at = Carbon::now()->addDays(14);

	$user->save();

### Specifying Additional User Details

If you would like to specify additional customer details, you may do so by passing them as second argument to the `create` method:

	$user->subscription('monthly')->create($creditCardToken, [
		'email' => $email, 'name' => 'Joe Doe'
	]);

To learn more about the additional fields supported by Conekta, check out Conekta's [documentation on customer creation](https://www.conekta.io/es/docs/api#crear-cliente).

## Single Charges

If you would like to make a "one off" charge against a subscribed customer's credit card, you may use the `charge` method:

	$user->charge(100);

The `charge` method accepts the amount you would like to charge in the **lowest denominator of the currency**. So, for example, the example above will charge 100 cents, or $1.00, against the user's credit card.

The `charge` method accepts an array as its second argument, allowing you to pass any options you wish to the underlying Conekta charge creation:

	$user->charge(100, [
		'card' => $token,
	]);

The `charge` method will return `false` if the charge fails. This typically indicates the charge was denied:

	if ( ! $user->charge(100))
	{
		// The charge was denied...
	}

If the charge is successful, the full Conekta response will be returned from the method.

## Swapping Subscriptions

To swap a user to a new subscription, use the `swap` method:

	$user->subscription('premium')->swap();

If the user is on trial, the trial will be maintained as normal. Also, if a "quantity" exists for the subscription, that quantity will also be maintained.

## Cancelling A Subscription

Cancelling a subscription is a walk in the park:

	$user->subscription()->cancel();

When a subscription is cancelled, Cashier will automatically set the `subscription_ends_at` column on your database. This column is used to know when the `subscribed` method should begin returning `false`. For example, if a customer cancels a subscription on March 1st, but the subscription was not scheduled to end until March 5th, the `subscribed` method will continue to return `true` until March 5th.

## Resuming A Subscription

If a user has cancelled their subscription and you wish to resume it, use the `resume` method:

	$user->subscription('monthly')->resume($creditCardToken);

If the user cancels a subscription and then resumes that subscription before the subscription has fully expired, they will not be billed immediately. Their subscription will simply be re-activated, and they will be billed on the original billing cycle.

## Checking Subscription Status

To verify that a user is subscribed to your application, use the `subscribed` command:

	if ($user->subscribed())
	{
		//
	}

The `subscribed` method makes a great candidate for a [route middleware](/docs/5.0/middleware):

	public function handle($request, Closure $next)
	{
		if ($request->user() && ! $request->user()->subscribed())
		{
			return redirect('billing');
		}

		return $next($request);
	}

You may also determine if the user is still within their trial period (if applicable) using the `onTrial` method:

	if ($user->onTrial())
	{
		//
	}

To determine if the user was once an active subscriber, but has cancelled their subscription, you may use the `cancelled` method:

	if ($user->cancelled())
	{
		//
	}

You may also determine if a user has cancelled their subscription, but are still on their "grace period" until the subscription fully expires. For example, if a user cancels a subscription on March 5th that was scheduled to end on March 10th, the user is on their "grace period" until March 10th. Note that the `subscribed` method still returns `true` during this time.

	if ($user->onGracePeriod())
	{
		//
	}

The `everSubscribed` method may be used to determine if the user has ever subscribed to a plan in your application:

	if ($user->everSubscribed())
	{
		//
	}

The `onPlan` method may be used to determine if the user is subscribed to a given plan based on its ID:

	if ($user->onPlan('monthly'))
	{
		//
	}

## Handling Conekta Webhooks
Conekta can notify your application of a variety of events via webhooks. By default, a route that points to Cashier's webhook controller is automatically registered by the Cashier service provider. This controller will handle all incoming webhook requests.

By default, the Conekta Cashier webhook controller will automatically handle cancelling subscriptions that have too many failed charges (as defined by your Conekta settings), customer updates, customer deletions, subscription updates, and payment method changes; however, as we'll soon discover, you can extend this controller to handle any Conekta webhook event you like.

To ensure your application can handle Conekta webhooks, be sure to configure the webhook URL in the Conekta control panel. By default, Cashier's webhook controller responds to the /conekta/webhook URL path.

For convenience, Cashier includes a cashier:webhook Artisan command. This command will create a webhook in Conekta that listens to all of the events required by Cashier:

	php artisan cashier:webhook

By default, the created webhook will point to the URL defined by the APP_URL environment variable and the cashier.webhook route that is included with Cashier. You may provide the --url option when invoking the command if you would like to use a different URL:

	php artisan cashier:webhook --url "https://example.com/conekta/webhook"

### Defining Webhook Event Handlers

Cashier automatically handles subscription cancellations for failed charges and other common Conekta webhook events. However, if you have additional webhook events you would like to handle, you may do so by listening to the following events that are dispatched by Cashier:

- Controlla\ConektaCashier\Events\WebhookReceived
- Controlla\ConektaCashier\Events\WebhookHandled

Both events contain the full payload of the Conekta webhook. For example, if you wish to handle the charge.paid webhook, you may register a listener that will handle the event:

	<?php
	
	namespace App\Listeners;
	
	use Controlla\ConektaCashier\Events\WebhookReceived;
	
	class ConektaEventListener
	{
			/**
			* Handle received Conekta webhooks.
			*
			* @param  \Controlla\ConektaCashier\Events\WebhookReceived  $event
			* @return void
			*/
			public function handle(WebhookReceived $event)
			{
					if ($event->payload['type'] === 'charge.paid') {
							// Handle the incoming event...
					}
			}
	}

Once your listener has been defined, you may register it within your application's EventServiceProvider:

	<?php
	
	namespace App\Providers;
	
	use App\Listeners\ConektaEventListener;
	use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
	use Controlla\ConektaCashier\Events\WebhookReceived;
	
	class EventServiceProvider extends ServiceProvider
	{
			protected $listen = [
					WebhookReceived::class => [
							ConektaEventListener::class,
					],
			];
	}

## Testing

When testing an application that uses Cashier, you may mock the actual HTTP requests to the Conekta API; however, this requires you to partially re-implement Cashier's own behavior. Therefore, we recommend allowing your tests to hit the actual Conekta API. While this is slower, it provides more confidence that your application is working as expected and any slow tests may be placed within their own PHPUnit testing group.

When testing, remember that Cashier itself already has a great test suite, so you should only focus on testing the subscription and payment flow of your own application and not every underlying Cashier behavior.

To get started, add the testing version of your Conekta secret to your phpunit.xml file:

	<env name="CONEKTA_SECRET" value="<your-key>"/>

Now, whenever you interact with Cashier while testing, it will send actual API requests to your Conekta testing environment.
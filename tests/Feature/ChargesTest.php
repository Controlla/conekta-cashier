<?php
namespace Controlla\ConektaCashier\Tests\Feature;

use Controlla\ConektaCashier\Order;
use Controlla\ConektaCashier\Tests\Feature\FeatureTestCase;

class ChargesTest extends FeatureTestCase
{
    public function test_customer_can_be_charged()
    {
        $user = $this->createCustomer('customer_can_be_charged');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->addPaymentMethod('tok_test_visa_4242');

        $response = $user->charge(1000, $paymentMethod->id);

        $this->assertInstanceOf(Order::class, $response);
        $this->assertEquals(1000, $response->rawAmount());
    }
}
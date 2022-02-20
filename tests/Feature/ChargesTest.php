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

        $response = $user->charge(1000);

        $this->assertInstanceOf(Order::class, $response);
        $this->assertEquals(1000, $response->rawAmount());
    }

    public function test_customer_can_be_charged_monthly_installments()
    {
        $user = $this->createCustomer('customer_can_be_charged_monthly_installments');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->addPaymentMethod('tok_test_visa_4242');

        $response = $user->charge(1000, null, [], 3);

        $this->assertInstanceOf(Order::class, $response);
        $this->assertEquals(1000, $response->rawAmount());
    }

    public function test_customer_can_be_refunded()
    {
        $user = $this->createCustomer('customer_can_be_refunded');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->addPaymentMethod('tok_test_visa_4242');

        $response = $user->charge(1000);

        $refund = $user->refund($response->id, 500);

        $this->assertEquals('partially_refunded', $refund->payment_status);
    }
}
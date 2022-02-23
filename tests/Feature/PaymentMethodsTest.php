<?php

namespace Controlla\ConektaCashier\Tests\Feature;

use Controlla\ConektaCashier\PaymentMethod;

class PaymentMethodsTest extends FeatureTestCase
{

    public function test_we_can_add_payment_methods()
    {
        $user = $this->createCustomer('we_can_add_payment_methods');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->addPaymentMethod('tok_test_visa_4242');

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals('visa', $paymentMethod->brand);
        $this->assertEquals('4242', $paymentMethod->last4);
        $this->assertTrue($user->hasPaymentMethod());
        $this->assertFalse($user->hasDefaultPaymentMethod());
    }

    public function test_we_can_delete_payment_methods()
    {
        $user = $this->createCustomer('we_can_delete_payment_methods');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->addPaymentMethod('tok_test_visa_4242');

        $this->assertCount(1, $user->paymentMethods());
        $this->assertTrue($user->hasPaymentMethod());

        $user->removePaymentMethod($paymentMethod->asConektaPaymentMethod());

        $this->assertCount(0, $user->paymentMethods());
        $this->assertFalse($user->hasPaymentMethod());
    }

    public function test_we_can_delete_the_default_payment_method()
    {
        $user = $this->createCustomer('we_can_delete_the_default_payment_method');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->updateDefaultPaymentMethod('tok_test_visa_4242');

        $this->assertCount(1, $user->paymentMethods());
        $this->assertTrue($user->hasPaymentMethod());
        $this->assertTrue($user->hasDefaultPaymentMethod());

        $user->removePaymentMethod($paymentMethod->asConektaPaymentMethod());

        $this->assertCount(0, $user->paymentMethods());
        $this->assertNull($user->defaultPaymentMethod());
        $this->assertNull($user->card_type);
        $this->assertNull($user->last_four);
        $this->assertFalse($user->hasPaymentMethod());
        $this->assertFalse($user->hasDefaultPaymentMethod());
    }

    public function test_we_can_set_a_default_payment_method()
    {
        $user = $this->createCustomer('we_can_set_a_default_payment_method');
        $user->createAsConektaCustomer();

        $paymentMethod = $user->updateDefaultPaymentMethod('tok_test_visa_4242');

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals('visa', $paymentMethod->brand);
        $this->assertEquals('4242', $paymentMethod->last4);
        $this->assertTrue($user->hasDefaultPaymentMethod());

        $paymentMethod = $user->defaultPaymentMethod();

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals('visa', $paymentMethod->brand);
        $this->assertEquals('visa', $user->card_type);
        $this->assertEquals('4242', $paymentMethod->last4);
        $this->assertEquals('4242', $user->last_four);
    }

    public function test_we_can_retrieve_all_payment_methods()
    {
        $user = $this->createCustomer('we_can_retrieve_all_payment_methods');
        $customer = $user->createAsConektaCustomer();

        $user->addPaymentMethod('tok_test_visa_4242');
        $user->addPaymentMethod('tok_test_mastercard_4444');

        $paymentMethods = $user->paymentMethods();

        $this->assertCount(2, $paymentMethods);
        $this->assertEquals('mastercard', $paymentMethods->first()->brand);
        $this->assertEquals('visa', $paymentMethods->last()->brand);
    }

    public function test_we_delete_all_payment_methods()
    {
        $user = $this->createCustomer('we_delete_all_payment_methods');
        $customer = $user->createAsConektaCustomer();

        $user->addPaymentMethod('tok_test_visa_4242');
        $user->addPaymentMethod('tok_test_mastercard_4444');

        $paymentMethods = $user->paymentMethods();

        $this->assertCount(2, $paymentMethods);

        $user->deletePaymentMethods();

        $paymentMethods = $user->paymentMethods();

        $this->assertCount(0, $paymentMethods);
    }
}

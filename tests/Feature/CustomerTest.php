<?php
namespace Controlla\ConektaCashier\Tests\Feature;

class CustomerTest extends FeatureTestCase
{
    public function test_customers_in_conekta_can_be_updated()
    {
        $user = $this->createCustomer('customers_in_conekta_can_be_updated');

        $customer = $user->createAsConektaCustomer();


        $this->assertEquals('Ivan Sotelo', $customer->name);

        $customer = $user->updateConektaCustomer(['name' => 'Mohamed Said']);

        $this->assertEquals('Mohamed Said', $customer->name);
    }

    public function test_customer_details_can_be_synced_with_conekta()
    {
        $user = $this->createCustomer('customer_details_can_be_synced_with_conekta');
        $user->createAsConektaCustomer();

        $user->name = 'Mohamed Said';
        $user->email = 'mohamed@example.com';
        $user->phone = '+32 499 00 00 00';

        $customer = $user->syncConektaCustomerDetails();

        $this->assertEquals('Mohamed Said', $customer->name);
        $this->assertEquals('mohamed@example.com', $customer->email);
        $this->assertEquals('+32 499 00 00 00', $customer->phone);
    }
}
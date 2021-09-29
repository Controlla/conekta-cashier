<?php
namespace Controlla\ConektaCashier\Tests\Feature;

class CustomerTest extends FeatureTestCase
{
    public function test_customers_in_conekta_can_be_updated()
    {
        $user = $this->createCustomer('customers_in_conekta_can_be_updated');

        $customer = $user->createAsConektaCustomer();


        $this->assertEquals('Taylor Otwell', $customer->name);

        $customer = $user->updateConektaCustomer(['name' => 'Mohamed Said']);

        $this->assertEquals('Mohamed Said', $customer->name);
    }
}
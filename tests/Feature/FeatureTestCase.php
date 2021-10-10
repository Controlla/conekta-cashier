<?php

namespace Controlla\ConektaCashier\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Controlla\ConektaCashier\Cashier;
use Controlla\ConektaCashier\Tests\Fixtures\User;
use Controlla\ConektaCashier\Tests\TestCase;
use Conekta\ConektaClient;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }

    protected static function conekta(array $options = [])
    {
        return new Cashier(array_merge(['api_key' => getenv('CONEKTA_SECRET')], $options));
    }

    protected function createCustomer($description = 'taylor', array $options = []): User
    {
        return User::create(array_merge([
            'email' => "{$description}@cashier-test.com",
            'name' => 'Ivan Sotelo',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ], $options));
    }
}
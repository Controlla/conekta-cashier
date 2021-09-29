<?php

namespace Controlla\ConektaCashier\Tests;

use Controlla\ConektaCashier\CashierServiceProvider;
use Controlla\ConektaCashier\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{

    protected function getPackageProviders($app)
    {
        return [CashierServiceProvider::class];
    }
}
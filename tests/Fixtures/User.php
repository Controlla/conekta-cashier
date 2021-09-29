<?php

namespace Controlla\ConektaCashier\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Model;
use Illuminate\Notifications\Notifiable;
use Controlla\ConektaCashier\Billable;

class User extends Model
{
    use Billable, Notifiable;

    protected $guarded = [];

}

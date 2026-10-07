<?php

namespace IlBronza\Notifications\Facades;

use Illuminate\Support\Facades\Facade;

/** @method static string renderHeaderNotifications() */
class Notifications extends Facade
{
    protected static function getFacadeAccessor() { return 'notifications'; }
}

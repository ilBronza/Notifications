<?php

namespace IlBronza\Notifications\Http\Models;

class Notification extends BaseNotificationModel
{
    protected static function boot()
    {
        static::addGlobalScope(function($query)
            {
                return $query->whereNull('scheduled_for');
            });

        parent::boot();
    }
}
<?php

namespace IlBronza\Notifications\Http\Models;

class ScheduledNotification extends BaseNotificationModel
{
    protected static function boot()
    {
        static::addGlobalScope(function($query)
            {
                return $query->whereNotNull('scheduled_for');
            });

        parent::boot();
    }
}
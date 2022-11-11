<?php

namespace IlBronza\Notifications\Facades;

use Illuminate\Support\Facades\Notification as BaseNotificationFacade;

class ScheduledNotification extends BaseNotificationFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ScheduledChannelManager::class;
    }
}

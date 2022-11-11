<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\Http\Helpers\NotificationGetterHelper;

class NotificationManager
{
    public function renderHeaderNotifications()
    {
        $notifications = NotificationGetterHelper::getHeaderNotifications($secondsValidity = 1200);

        return view('notifications::_header', compact('notifications'))->render();
    }
}
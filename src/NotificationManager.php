<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\Http\Helpers\NotificationGetterHelper;

class NotificationManager
{
    public function renderHeaderNotifications()
    {
        if (NotificationPreference::hiddenFor(auth()->user())) {
            return '';
        }
        $notifications = NotificationGetterHelper::getHeaderNotifications($secondsValidity = 1200);

        return view('notifications::_header', compact('notifications'))->render();
    }
}
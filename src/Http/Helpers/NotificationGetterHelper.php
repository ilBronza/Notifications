<?php

namespace IlBronza\Notifications\Http\Helpers;

use Auth;
use IlBronza\Notifications\Http\Models\Notification;
use IlBronza\Notifications\Http\Models\ScheduledNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationGetterHelper
{
    static function getHeaderNotificationsByModel(Model $model)
    {
        $polimorphicFilter = [
            'notifiable_type' => get_class($model),
            'notifiable_id' => $model->getKey()
        ];

        // $notifications = Notification::where($polimorphicFilter)->ValidByDate()->unread()->get();

        $scheduledNotifications = ScheduledNotification::where($polimorphicFilter)->ValidByDate()->unread()->get();

        return $scheduledNotifications;

        return $notifications->merge($scheduledNotifications);
    }

    static function getUserHeaderNotifications() : ? Collection
    {
        return static::getHeaderNotificationsByModel(Auth::user());
    }

    static function getRolesHeaderNotifications() : ? Collection
    {
        $result = collect();

        $roles = Auth::user()->roles()->get();

        foreach($roles as $role)
            $result->merge(
                static::getHeaderNotificationsByModel($role)
            );

        return $result;
    }

    static function resetHeaderSessionCache()
    {
        return session()->forget(
            config('notifications.headerNotifications.sessionKey')
        );
    }

    static function getHeaderNotifications(int $secondsValidity = 1200)
    {
        if(! $user = Auth::user())
            return null;

    	if(($sessionNotifications = session(
            config('notifications.headerNotifications.sessionKey')
        ))&&($sessionNotifications['timestamp'] > time() - $secondsValidity))
    		return $sessionNotifications['notifications'];

        $userNotifications = static::getUserHeaderNotifications();
        $rolesNotifications = static::getRolesHeaderNotifications();

        $notifications = $userNotifications->merge(
            $rolesNotifications
        );

        session()->put(
            config('notifications.headerNotifications.sessionKey')
            , [
        	'timestamp' => time(),
        	'notifications' => $notifications
        ]);

        return $notifications;
    }
}
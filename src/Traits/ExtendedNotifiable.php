<?php

namespace IlBronza\Notifications\Traits;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\RoutesNotifications;

trait ExtendedNotifiable
{
    use HasDatabaseNotifications, RoutesNotifications;

    public function notify($instance)
    {
        return \IlBronza\Notifications\Facades\Notification::send($this, $instance);
    }

    public function notifyNow($instance, ?array $channels = null)
    {
        return \IlBronza\Notifications\Facades\Notification::sendNow($this, $instance, $channels);
    }

    public function notifications()
    {
        return $this->morphMany(ExtendedDatabaseNotification::class, 'notifiable')->latest();
    }

    public function getBaseQuery()
    {
        return ExtendedDatabaseNotification::query()->inboxFor($this)->with('parent')->latest();
    }

    public function getNotificationsCount()
    {
        return $this->getBaseQuery()->toManage()->count();
    }

    public function getUnmanagedNotifications()
    {
        return $this->getBaseQuery()->toManage()->get();
    }

    public function getAllNotifications()
    {
        return $this->getBaseQuery()->get();
    }

    public function getArchivedNotifications()
    {
        return $this->getBaseQuery()->managed()->get();
    }
}

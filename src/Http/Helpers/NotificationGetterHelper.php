<?php

namespace IlBronza\Notifications\Http\Helpers;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Support\Recipients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NotificationGetterHelper
{
    protected static function headerQuery()
    {
        return ExtendedDatabaseNotification::query()->validByDate()->unread()->toManage()
            ->where('headerbar', true)->orderByDesc('priority')->latest();
    }

    public static function getHeaderNotificationsByModel(Model $model)
    {
        return static::headerQuery()->whereIn('notifiable_type', [$model->getMorphClass(), get_class($model)])
            ->where('notifiable_id', $model->getKey())->with('parent')
            ->limit(config('notifications.headerNotifications.limit', 50))->get();
    }

    public static function getUserHeaderNotifications(): Collection
    {
        return Auth::user() ? static::getHeaderNotificationsByModel(Auth::user()) : collect();
    }

    public static function getRolesHeaderNotifications(): Collection
    {
        $result = collect();
        if ($user = Auth::user()) {
            foreach (Recipients::roles($user) as $role) {
                $result = $result->merge(static::getHeaderNotificationsByModel($role));
            }
        }
        return $result;
    }

    public static function resetHeaderSessionCache()
    {
        session()->forget(config('notifications.headerNotifications.sessionKey', 'headerNotifications'));
    }

    // The argument remains for compatibility. Queries are fresh so queued deliveries are visible immediately.
    public static function getHeaderNotifications(int $secondsValidity = 1200)
    {
        $user = Auth::user();
        return $user ? static::headerQuery()->inboxFor($user)->with('parent')
            ->limit(config('notifications.headerNotifications.limit', 50))->get() : collect();
    }
}

<?php

namespace IlBronza\Notifications;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $guarded = [];
    protected $casts = ['header_hidden' => 'boolean'];

    public static function hiddenFor(?Model $user): bool
    {
        return $user ? (bool) static::where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->getKey())->value('header_hidden') : false;
    }

    public static function setHeaderHidden(Model $user, bool $hidden): void
    {
        static::updateOrCreate(['notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->getKey()], ['header_hidden' => $hidden]);
    }
}

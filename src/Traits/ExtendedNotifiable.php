<?php

namespace IlBronza\Notifications\Traits;

use IlBronza\AccountManager\Models\Role;
use IlBronza\Notifications\ExtendedDatabaseNotification;
use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\RoutesNotifications;

trait ExtendedNotifiable
{
    use HasDatabaseNotifications, RoutesNotifications;

    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(ExtendedDatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    public function getBaseQuery()
    {
        $rolesIds = $this->roles->pluck('id');

        $parentedIds = ExtendedDatabaseNotification::select('parent_id')
            ->where(function($query) use($rolesIds)
            {
                $query->where('notifiable_type', Role::class);
                $query->whereIn('notifiable_id', $rolesIds);
            })->orWhere(function($query)
            {
                $query->where('notifiable_type', get_class($this));
                $query->where('notifiable_id', $this->getKey());
            })->whereNotNull('parent_id')->get();

        return ExtendedDatabaseNotification::where(function($query) use($rolesIds)
        {
            $query->where('notifiable_type', Role::class);
            $query->whereIn('notifiable_id', $rolesIds);
        })->orWhere(function($query)
        {
            $query->where('notifiable_type', get_class($this));
            $query->where('notifiable_id', $this->getKey());
        })->with('children')->whereNotIn('id', $parentedIds);
    }

    public function getNotificationsCount()
    {
        return $this->getBaseQuery()->count();
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

<?php

namespace IlBronza\Notifications\Observers;

use IlBronza\Notifications\ExtendedDatabaseNotification;

class ExtendedDatabaseNotificationObserver
{
    public static $parentingUpdatingFields = ['managed_by', 'managed_at', 'managed'];

    public function updating(ExtendedDatabaseNotification $notification)
    {
        if ($notification->isDirty('managed_at') && ! $notification->isDirty('managed_by')) {
            $notification->managed_by = $notification->managed_at ? auth()->id() : null;
        }
    }

    public function updated(ExtendedDatabaseNotification $notification)
    {
        $fields = array_intersect_key($notification->getChanges(), array_flip(static::$parentingUpdatingFields));
        if ($fields === []) {
            return;
        }
        $root = $notification->parent_id ?: $notification->getKey();
        // A single query avoids recursive observers. Read state belongs to each receipt.
        ExtendedDatabaseNotification::query()->where(function ($query) use ($root) {
            $query->whereKey($root)->orWhere('parent_id', $root);
        })->where('id', '!=', $notification->getKey())->update($fields);
    }
}

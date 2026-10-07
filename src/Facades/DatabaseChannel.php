<?php

namespace IlBronza\Notifications\Facades;

use IlBronza\Notifications\ChildDatabaseNotification;
use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\ScheduledNotification;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;
use LogicException;

class DatabaseChannel extends BaseDatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        // Stable IDs make database retries safe; an existing receipt is never overwritten.
        $relation = $notifiable->routeNotificationFor('database', $notification);
        $payload = $this->buildPayload($notifiable, $notification);
        if (ExtendedDatabaseNotification::whereKey($payload['id'])->where(function ($query) use ($notifiable) {
            $query->where('notifiable_type', '!=', $notifiable->getMorphClass())->orWhere('notifiable_id', '!=', $notifiable->getKey());
        })->exists()) {
            throw new LogicException('This notification ID already belongs to a different recipient.');
        }
        return $relation->firstOrCreate(['id' => $payload['id']], $payload);
    }

    public function _buildPayload($notifiable, Notification $notification): array
    {
        $payload = parent::buildPayload($notifiable, $notification);
        $payload += [
            'created_by' => $notification->createdBy ?? ($notification->user ?? null)?->getKey(),
            'link' => method_exists($notification, 'getLink') ? $notification->getLink() : null,
            'link_text' => method_exists($notification, 'getLinkText') ? $notification->getLinkText() : null,
            'parent_id' => method_exists($notification, 'getParentId') ? $notification->getParentId() : null,
        ];
        if ($notification instanceof ScheduledNotification) {
            $payload += [
                'scheduled_for' => $notification->getScheduledDate(),
                'valid_from' => $notification->getValidFromDate(),
                'valid_to' => $notification->getValidToDate(),
                'priority' => $notification->getPriority(),
                'headerbar' => $notification->appearsInHeaderbar(),
            ];
        }
        if ($notification instanceof ChildDatabaseNotification) {
            $parent = ExtendedDatabaseNotification::findOrFail($notification->getParentId());
            $inherited = array_intersect_key($parent->getAttributes(), array_flip([
                'created_by', 'link', 'link_text', 'scheduled_for', 'valid_from', 'valid_to', 'priority', 'headerbar',
                'managed_by', 'managed_at', 'managed',
            ]));
            $payload = array_merge($payload, $inherited);
        }
        return $payload;
    }

    protected function buildPayload($notifiable, Notification $notification)
    {
        return $this->_buildPayload($notifiable, $notification);
    }
}

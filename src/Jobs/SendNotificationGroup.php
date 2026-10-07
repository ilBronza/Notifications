<?php

namespace IlBronza\Notifications\Jobs;

use IlBronza\Notifications\ChildDatabaseNotification;
use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Facades\ChannelManager;
use Illuminate\Notifications\ChannelManager as LaravelChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use RuntimeException;

class SendNotificationGroup extends SendQueuedNotifications
{
    public function __construct($notifiables, $notification, public array $ids)
    {
        parent::__construct($notifiables, $notification, ['database']);
    }

    public function handle(LaravelChannelManager $manager)
    {
        $manager = app(ChannelManager::class);
        (new ExtendedDatabaseNotification)->getConnection()->transaction(function () use ($manager) {
            foreach ($this->notifiables->values() as $index => $recipient) {
                $notification = clone $this->notification;
                $notification->parentId = $index === 0 ? null : $this->ids[0];
                $notification->id = $this->ids[$index];
                $manager->sendNow($recipient, $notification, ['database']);
                if (! ExtendedDatabaseNotification::whereKey($notification->id)->exists()) {
                    throw new RuntimeException('A notification in the group was rejected; no group was saved.');
                }
            }
        });
    }
}

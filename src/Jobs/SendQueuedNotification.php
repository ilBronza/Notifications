<?php

namespace IlBronza\Notifications\Jobs;

use IlBronza\Notifications\Facades\ChannelManager;
use Illuminate\Notifications\ChannelManager as LaravelChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;

class SendQueuedNotification extends SendQueuedNotifications
{
    public function handle(LaravelChannelManager $manager)
    {
        app(ChannelManager::class)->sendNow($this->notifiables, $this->notification, $this->channels);
    }
}

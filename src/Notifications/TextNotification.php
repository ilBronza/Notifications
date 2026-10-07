<?php

namespace IlBronza\Notifications\Notifications;

use IlBronza\Notifications\Notification;

class TextNotification extends Notification
{
    public function __construct(public string $message)
    {
        parent::__construct();
    }

    public function toArray($notifiable) { return ['message' => $this->message]; }
}

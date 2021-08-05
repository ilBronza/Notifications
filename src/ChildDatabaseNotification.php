<?php

namespace IlBronza\Notifications;

class ChildDatabaseNotification extends Notification
{
    public $notificationId;

    public function __construct(string $notificationId)
    {
        $this->parentId = $notificationId;

        parent::__construct();
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
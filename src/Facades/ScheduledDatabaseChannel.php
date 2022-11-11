<?php

namespace IlBronza\Notifications\Facades;

use Illuminate\Notifications\Notification;

class ScheduledDatabaseChannel extends DatabaseChannel
{
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        $basePayload = $this->_buildPayload($notifiable, $notification);

        $ownPayload = [
            'scheduled_for' => $notification->getScheduledDate(),
            'valid_from' => $notification->getValidFromDate(),
            'valid_to' => $notification->getValidToDate(),
            'priority' => $notification->getPriority(),
            'headerbar' => $notification->appearsInHeaderbar(),
        ];

        return array_merge(
            $basePayload,
            $ownPayload
        );
    }
}

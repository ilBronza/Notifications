<?php

namespace IlBronza\Notifications\Facades;

use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannel extends BaseDatabaseChannel
{
    public function _buildPayload($notifiable, Notification $notification) : array
    {
        return [
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $this->getData($notifiable, $notification),
            'created_by' => ($notification->user) ? $notification->user->getKey() : null,
            'link' => $notification->getLink(),
            'link_text' => $notification->getLinkText(),
            'read_at' => null,
            'parent_id' => $notification->getParentId()
        ];        
    }
    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        return $this->_buildPayload($notifiable, $notification);
    }
}

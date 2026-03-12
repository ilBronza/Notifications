<?php

namespace IlBronza\Notifications\Notifications;

use IlBronza\Notifications\Notification;

class AudioNotification extends Notification
{
    protected string $audioUrl;
    protected ?string $message;

    public function __construct(string $audioUrl, ?string $message = null)
    {
        parent::__construct();

        $this->audioUrl = $audioUrl;
        $this->message = $message ?? __('notifications::notifications.voiceMessage');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message' => $this->message,
            'audio_url' => $this->audioUrl,
            'type' => 'audio',
        ];
    }
}

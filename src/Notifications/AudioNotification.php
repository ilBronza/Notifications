<?php

namespace IlBronza\Notifications\Notifications;

use IlBronza\Notifications\Notification;

class AudioNotification extends Notification
{
    protected ?string $audioDisk = null;
    protected ?string $audioPath = null;

    // Existing URL-based notifications remain readable.
    public function __construct(protected string $audioUrl, protected ?string $message = null)
    {
        parent::__construct();
    }

    public static function fromStorage(string $disk, string $path, ?string $message = null): static
    {
        $notification = new static('', $message);
        $notification->audioDisk = $disk;
        $notification->audioPath = $path;
        return $notification;
    }

    public function toArray($notifiable)
    {
        $payload = ['message' => $this->message ?? __('notifications::notifications.voiceMessage'), 'type' => 'audio'];
        return $this->audioDisk
            ? $payload + ['audio_disk' => $this->audioDisk, 'audio_path' => $this->audioPath]
            : $payload + ['audio_url' => $this->audioUrl];
    }
}

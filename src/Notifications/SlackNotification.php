<?php

namespace IlBronza\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use LogicException;

class SlackNotification extends Notification
{
    use Queueable;

    public function __construct(public ?string $message = null, public ?string $url = null) {}

    public function via($notifiable) { return ['slack']; }

    public function toSlack($notifiable)
    {
        // Preserve webhook-based integrations when the legacy message API is installed.
        if (class_exists(\Illuminate\Notifications\Messages\SlackMessage::class)) {
            $message = (new \Illuminate\Notifications\Messages\SlackMessage)->content($this->message ?? '');
            if ($this->url) {
                $message->attachment(fn ($attachment) => $attachment->title('link', $this->url));
            }
            return $message;
        }
        if (class_exists(\Illuminate\Notifications\Slack\SlackMessage::class)) {
            return (new \Illuminate\Notifications\Slack\SlackMessage)->text(
                ($this->message ?? '').($this->url ? "\n".$this->url : '')
            );
        }
        throw new LogicException('Install and configure laravel/slack-notification-channel before sending Slack notifications.');
    }
}

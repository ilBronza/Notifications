<?php

namespace IlBronza\Notifications\Facades;

use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Notifications\NotificationSender as BaseNotificationSender;

class NotificationSender extends BaseNotificationSender
{
    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if ($notification instanceof ShouldQueue) {
            return $this->queueNotification($notifiables, $notification);
        }

        return $this->sendNow($notifiables, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  \Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        $notificationIds = collect();

        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (empty($viaChannels = $channels ?: $notification->via($notifiable))) {
                continue;
            }

            $this->withLocale($this->preferredLocale($notifiable, $notification), function () use ($viaChannels, $notifiable, $original, $notificationIds) {
                $notificationId = Str::uuid()->toString();

                $notificationIds->push($notificationId);

                foreach ((array) $viaChannels as $channel) {
                    if (! ($notifiable instanceof AnonymousNotifiable && $channel === 'database')) {
                        $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
                    }
                }
            });
        }

        if(count($notificationIds) == 1)
            return $notificationIds->first();

        throw new \Exception('too many notifications ids');
        return $notificationIds;
    }

    /**
     * Queue the given notification instances.
     *
     * @param  mixed  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    protected function queueNotification($notifiables, $notification)
    {
        $notificationIds = collect();
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            $notificationId = Str::uuid()->toString();
            $notificationIds->push($notificationId);

            foreach ((array) $original->via($notifiable) as $channel) {
                $notification = clone $original;

                $notification->id = $notificationId;

                if (! is_null($this->locale)) {
                    $notification->locale = $this->locale;
                }

                $queue = $notification->queue;

                if (method_exists($notification, 'viaQueues')) {
                    $queue = $notification->viaQueues()[$channel] ?? null;
                }

                $this->bus->dispatch(
                    (new SendQueuedNotifications($notifiable, $notification, [$channel]))
                            ->onConnection($notification->connection)
                            ->onQueue($queue)
                            ->delay(is_array($notification->delay) ?
                                    ($notification->delay[$channel] ?? null)
                                    : $notification->delay
                            )
                            ->through(
                                array_merge(
                                    method_exists($notification, 'middleware') ? $notification->middleware() : [],
                                    $notification->middleware ?? []
                                )
                            )
                );
            }
        }

        return $notificationIds;
    }
}

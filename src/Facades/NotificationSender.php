<?php

namespace IlBronza\Notifications\Facades;

use IlBronza\Notifications\Jobs\SendQueuedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\NotificationSender as BaseNotificationSender;
use Illuminate\Support\Str;

class NotificationSender extends BaseNotificationSender
{
    public function send($notifiables, $notification)
    {
        return $notification instanceof ShouldQueue
            ? $this->queueNotification($notifiables, $notification)
            : $this->sendNow($notifiables, $notification);
    }

    public function sendNow($notifiables, $notification, ?array $channels = null)
    {
        $notifiables = $this->formatNotifiables($notifiables);
        if ($notification->id && count($notifiables) > 1) {
            throw new \InvalidArgumentException('An explicit notification ID can only identify one recipient.');
        }
        $ids = collect();
        foreach ($notifiables as $notifiable) {
            $via = $channels ?? $notification->via($notifiable);
            if (empty($via)) {
                continue;
            }
            $this->withLocale($this->preferredLocale($notifiable, $notification), function () use ($notifiable, $notification, $via, $ids) {
                $id = $notification->id ?: (string) Str::uuid();
                $sent = false;
                foreach ($via as $channel) {
                    if ($notifiable instanceof AnonymousNotifiable && $channel === 'database') {
                        continue;
                    }
                    $sent = $this->sendToNotifiable($notifiable, $id, clone $notification, $channel) || $sent;
                }
                if ($sent) { $ids->push($id); }
            });
        }
        return $ids->count() === 1 ? $ids->first() : $ids;
    }

    protected function queueNotification($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);
        if ($notification->id && count($notifiables) > 1) {
            throw new \InvalidArgumentException('An explicit notification ID can only identify one recipient.');
        }
        $ids = collect();
        foreach ($notifiables as $notifiable) {
            $channels = $notification->via($notifiable);
            if (empty($channels)) {
                continue;
            }
            $id = $notification->id ?: (string) Str::uuid();
            foreach ($channels as $channel) {
                if ($notifiable instanceof AnonymousNotifiable && $channel === 'database') {
                    continue;
                }
                $copy = clone $notification;
                $copy->id = $id;
                if ($copy instanceof \IlBronza\Notifications\Notification) {
                    $copy->createdBy = $copy->createdBy ?? $copy->user?->getKey();
                    $copy->user = null;
                }
                $copy->locale = $copy->locale ?? $this->locale;
                $connection = method_exists($copy, 'viaConnections') ? ($copy->viaConnections()[$channel] ?? null) : ($copy->connection ?? null);
                $queue = method_exists($copy, 'viaQueues') ? ($copy->viaQueues()[$channel] ?? null) : ($copy->queue ?? null);
                $delay = method_exists($copy, 'withDelay') ? $copy->withDelay($notifiable, $channel) : ($copy->delay ?? null);
                $middleware = $copy->middleware ?? [];
                if (method_exists($copy, 'middleware')) {
                    $middleware = array_merge($copy->middleware($notifiable, $channel), $middleware);
                }
                $this->bus->dispatch((new SendQueuedNotification($notifiable, $copy, [$channel]))
                    ->onConnection($connection)->onQueue($queue)
                    ->delay(is_array($delay) ? ($delay[$channel] ?? null) : $delay)
                    ->through($middleware)->afterCommit());
            }
            $ids->push($id);
        }
        return $ids->count() === 1 ? $ids->first() : $ids;
    }

    protected function sendToNotifiable($notifiable, $id, $notification, $channel)
    {
        $notification->id = $notification->id ?: $id;
        if (! $this->shouldSendNotification($notifiable, $notification, $channel)) {
            return false;
        }
        $response = $this->manager->driver($channel)->send($notifiable, $notification);
        $this->events->dispatch(new \Illuminate\Notifications\Events\NotificationSent($notifiable, $notification, $channel, $response));
        return true;
    }
}

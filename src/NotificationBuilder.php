<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\Facades\Notification as NotificationFacade;
use IlBronza\Notifications\Jobs\SendNotificationGroup;
use IlBronza\Notifications\Support\Recipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class NotificationBuilder
{
    public $roles;
    public $users;
    public $notification;

    public function __construct()
    {
        $this->users = collect();
        $this->roles = collect();
    }

    public function roles($roles): self
    {
        $this->roles = Notification::parseRoles($roles);
        return $this;
    }

    public function users($users): self
    {
        $this->users = Notification::parseUsers($users);
        return $this;
    }

    public function notification(LaravelNotification $notification): self
    {
        $this->notification = $notification;
        return $this;
    }

    public function shiftRole(): ?Model { return $this->roles->shift(); }
    public function shiftUser(): ?Model { return $this->users->shift(); }

    public function send()
    {
        if (! $this->notification instanceof LaravelNotification) {
            throw new LogicException('Set a notification before calling send().');
        }
        $recipients = $this->roles->concat($this->users)->unique(fn (Model $model) => Recipients::key($model))->values();
        if ($recipients->isEmpty()) {
            throw new InvalidArgumentException('At least one recipient is required.');
        }
        foreach ($recipients as $recipient) {
            if ($recipient->getKey() === null) {
                throw new InvalidArgumentException('Recipients must have a primary key.');
            }
        }
        // Sharing applies to database-only notifications. Other channels must reach every recipient.
        $databaseOnly = $this->notification instanceof Notification
            && $recipients->every(fn ($recipient) => $this->notification->via($recipient) === ['database']);
        if ($recipients->count() === 1 || ! $databaseOnly) {
            return NotificationFacade::send($recipients, $this->notification);
        }

        $notification = clone $this->notification;
        $notification->id = $notification->id ?: (string) Str::uuid();
        $ids = $recipients->map(fn ($recipient, $index) => $index === 0 ? $notification->id : (string) Str::uuid())->all();
        if ($notification instanceof ShouldQueue) {
            $notification->createdBy = $notification->createdBy ?? $notification->user?->getKey();
            $notification->user = null;
        }
        $job = new SendNotificationGroup($recipients, $notification, $ids);
        if ($notification instanceof ShouldQueue) {
            $job->onConnection(method_exists($notification, 'viaConnections') ? ($notification->viaConnections()['database'] ?? null) : ($notification->connection ?? null));
            $job->onQueue(method_exists($notification, 'viaQueues') ? ($notification->viaQueues()['database'] ?? null) : ($notification->queue ?? null));
            $delay = method_exists($notification, 'withDelay') ? $notification->withDelay($recipients->first(), 'database') : ($notification->delay ?? null);
            $job->delay(is_array($delay) ? ($delay['database'] ?? null) : $delay);
            $middleware = $notification->middleware ?? [];
            if (method_exists($notification, 'middleware')) {
                $middleware = array_merge($notification->middleware($recipients->first(), 'database'), $middleware);
            }
            $job->through($middleware)->afterCommit();
            Bus::dispatch($job);
        } else {
            $job->handle(app(\Illuminate\Notifications\ChannelManager::class));
        }
        return $notification->id;
    }

    public function sendToSingleNotifiable($notifiable = null)
    {
        return $notifiable ? NotificationFacade::send($notifiable, $this->notification) : $this->send();
    }

    public function sendToMultipleNotifiables() { return $this->send(); }

    public function associateNotificationId($notifiable, string $notificationId)
    {
        return NotificationFacade::send($notifiable, new ChildDatabaseNotification($notificationId));
    }
}

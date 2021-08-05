<?php

namespace IlBronza\Notifications;

use IlBronza\AccountManager\Models\Role;
use IlBronza\Notifications\Facades\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationBuilder
{
    public $roles;
    public $users;

    public function __construct()
    {
        $this->users = collect();
        $this->roles = collect();
    }

    public function roles(Collection $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function users(Collection $users) : self
    {
        $this->users = $users;

        return $this;
    }

    public function notification($notification) : self
    {
        $this->notification = $notification;

        return $this;
    }

    private function isSingleNotifiable() : bool
    {
        if(($usersCount = count($this->users)) > 1)
            return false;

        if(($rolesCount = count($this->roles)) > 1)
            return false;

        return ! (($usersCount + $rolesCount) > 1);
    }

    private function getSingleNotifiable() : Model
    {
        if($user = $this->users->first())
            return $user;

        if($role = $this->roles->first())
            return $role;

        throw new \Exception('single notifiable missing');
    }

    public function send()
    {
        if($this->isSingleNotifiable())
            return $this->sendToSingleNotifiable();

        return $this->sendToMultipleNotifiables();
    }

    public function sendToSingleNotifiable($notifiable = null)
    {
        if(! $notifiable)
            $notifiable = $this->getSingleNotifiable();

        return Notification::send($notifiable, $this->notification);
    }

    public function shiftRole() : ? Role
    {
        if(! $this->roles)
            return null;

        return $this->roles->shift();
    }

    public function shiftUser() : ? User
    {
        if(! $this->users)
            return null;

        return $this->users->shift();
    }

    public function sendToMultipleNotifiables()
    {
        if($role = $this->shiftRole())
            $notificationId = Notification::send($role, $this->notification);

        elseif($user = $this->shiftUser())
            $notificationId = Notification::send($user, $this->notification);

        foreach(($this->users ?? []) as $user)
            $this->associateNotificationId($user, $notificationId);

        foreach(($this->roles ?? []) as $role)
            $this->associateNotificationId($role, $notificationId);
    }

    public function associateNotificationId($notifiable, string $notificationId)
    {
        Notification::send($notifiable, new ChildDatabaseNotification($notificationId));
    }
}
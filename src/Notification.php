<?php

namespace IlBronza\Notifications;

use Auth;
use IlBronza\AccountManager\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Str;

class Notification extends BaseNotification
{
    public $user;

    public $link;
    public $linkText;
    public $hasLink;

    public $parentId;

    public $commonId;

    public $via = ['database'];

    public function __construct()
    {
        $this->user = Auth::user();

        if($this->hasLink())
            $this->setLink();

        $this->commonId = Str::uuid();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->via;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function getLinkText()
    {
        return $this->linkText;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function hasLink()
    {
        return $this->hasLink;
    }

    static function getUserNotificationsCount()
    {
        if(! $user = Auth::user())
            return null;

        return cache()->remember(
            'notificationsCount' . $user->getKey(),
            1200,
            function() use ($user)
            {
                return $user->getNotificationsCount();
            }
        );
    }

    static function parseUsers($users)
    {
        if(is_integer($users))
            return User::where('id', $users)->get();

        if(is_array($users))
            return User::whereIn('name', $users)->get();

        if(is_string($users))
            return User::where('name', $users)->get();

        if($users instanceof User)
            return collect([$role]);

        if($users instanceof Collection)
            return $users;

        throw new \Exception('not valid roles given');
    }

    static function parseRoles($roles)
    {
        if(is_array($roles))
            return Role::whereIn('name', $roles)->get();

        if(is_string($roles))
            return Role::where('name', $roles)->get();

        if($roles instanceof Role)
            return collect([$role]);

        if($roles instanceof Collection)
            return $roles;
        
        throw new \Exception('not valid roles given');
    }

    static function build()
    {
        return new NotificationBuilder();
    }

    static function roles($roles)
    {
        $roles = static::parseRoles($roles);

        return static::build()->roles($roles);
    }

    static function users($users)
    {
        $users = static::parseUsers($users);

        return $notification->setUsers($users);
    }
}
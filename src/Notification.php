<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\Support\Recipients;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Notification extends BaseNotification
{
    public $user;
    public $createdBy;
    public $link;
    public $linkText;
    public $hasLink;
    public $parentId;
    public $commonId;
    public $via = ['database'];

    public function __construct()
    {
        $this->user = Auth::user();
        $this->createdBy = $this->user?->getKey();
        if ($this->hasLink()) {
            $this->setLink();
        }
        $this->commonId = Str::uuid();
    }

    public function via($notifiable) { return $this->via; }
    public function getLink() { return $this->link; }
    public function getLinkText() { return $this->linkText; }
    public function getParentId() { return $this->parentId; }
    public function hasLink() { return $this->hasLink; }

    public static function getUserNotificationsCount()
    {
        $user = Auth::user();
        return $user ? ExtendedDatabaseNotification::query()->inboxFor($user)->toManage()->count() : null;
    }

    public static function parseUsers($users)
    {
        return Recipients::resolve($users, config('notifications.user_model') ?: config('auth.providers.users.model'));
    }

    public static function parseRoles($roles)
    {
        return Recipients::resolve($roles, config('notifications.role_model', \IlBronza\AccountManager\Models\Role::class));
    }

    public static function build() { return new NotificationBuilder; }
    public static function roles($roles) { return static::build()->roles($roles); }
    public static function users($users) { return static::build()->users($users); }
}

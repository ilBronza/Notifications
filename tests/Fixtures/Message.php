<?php
namespace IlBronza\Notifications\Tests\Fixtures;
use IlBronza\Notifications\Notification;
class Message extends Notification
{
    public function toArray($notifiable) { return ['message' => 'A private message']; }
}

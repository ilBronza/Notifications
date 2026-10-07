<?php
namespace IlBronza\Notifications\Tests\Fixtures;
use IlBronza\Notifications\ScheduledNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
class ScheduledMessage extends ScheduledNotification implements ShouldQueue
{
    use Queueable;
    public $data = ['priority' => 0];
    public function toArray($notifiable) { return ['message' => 'Scheduled message']; }
}

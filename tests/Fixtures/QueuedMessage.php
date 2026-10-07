<?php
namespace IlBronza\Notifications\Tests\Fixtures;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
class QueuedMessage extends Message implements ShouldQueue { use Queueable; }

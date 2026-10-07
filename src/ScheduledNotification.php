<?php

namespace IlBronza\Notifications;

use Carbon\Carbon;
use IlBronza\Notifications\Notification;

class ScheduledNotification extends Notification
{
	public $appearsInHeaderBar = true;
    protected ?Carbon $defaultScheduledDate = null;

    public function scheduledFor(Carbon $date): static
    {
        $this->defaultScheduledDate = $date->copy();
        return $this;
    }

	public function getScheduledDate()
	{
		return $this->scheduledDate ?? $this->defaultScheduledDate ?? throw new \LogicException('Set a scheduled date before sending.');
	}

	public function getNotificationDays()
	{
		return $this->notificationDays ?? config('notifications.scheduled.daysBefore');
	}

	public function getValidFromDate() : Carbon
	{
		$date = $this->getScheduledDate()->copy();

		return $date->subDays(
			$this->getNotificationDays()
		);
	}

	public function getValidToDate() : ? Carbon
	{
		return $this->validTo ?? null;
	}

	public function getPriority()
	{
		if(! ($this->data ?? false))
			return null;
		
		if(! isset($this->data['priority']))
			return null;

		return $this->data['priority'];
	}

	public function appearsInHeaderbar() : bool
	{
		return $this->appearsInHeaderBar;
	}
}
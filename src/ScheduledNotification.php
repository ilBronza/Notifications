<?php

namespace IlBronza\Notifications;

use Carbon\Carbon;
use IlBronza\Notifications\Notification;

class ScheduledNotification extends Notification
{
	public $appearsInHeaderBar = true;

	public function getScheduledDate()
	{
		return $this->scheduledDate;
	}

	public function getNotificationDays()
	{
		return $this->notificationDays ?? config('notifications.scheduled.daysBefore');
	}

	public function getValidFromDate() : Carbon
	{
		$date = $this->scheduledDate->copy();

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
		
		if(! ($this->data['priority'] ?? false))
			return null;

		return $this->data['priority'];
	}

	public function appearsInHeaderbar() : bool
	{
		return $this->appearsInHeaderBar;
	}
}
<?php

namespace IlBronza\Notifications\Observers;

use IlBronza\Notifications\ExtendedDatabaseNotification;

class ExtendedDatabaseNotificationObserver
{
	static $parentingUpdatingFields = ['managed_by', 'managed_at', 'managed'];

	private function checkParentingUpdatingFields(ExtendedDatabaseNotification $notification)
	{
		$propagatingFields = static::$parentingUpdatingFields;

		if(! $notification->isDirty($propagatingFields))
			return ;

		$dirty = $notification->getDirty();

		$notification->parentingGlobalPropagation(
			array_keys(array_intersect_key($dirty, array_flip($propagatingFields)))
		);
	}

	public function updated(ExtendedDatabaseNotification $notification)
	{
		//update managing fields to all notifications
		$this->checkParentingUpdatingFields($notification);

		//update read at only to father
		if($notification->isDirty('read_at'))
			if($notification->read_at)
				if($parent = $notification->parent)
					$parent->update(['read_at' => $notification->read_at]);
	}
}

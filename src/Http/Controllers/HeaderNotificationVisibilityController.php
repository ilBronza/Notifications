<?php

namespace IlBronza\Notifications\Http\Controllers;

use Illuminate\Routing\Controller;

class HeaderNotificationVisibilityController extends Controller
{
	public function show()
	{
		session()->put('header.notifications.visible', true);
        session()->forget('header.notifications.hidePermanently');
        \IlBronza\Notifications\NotificationPreference::setHeaderHidden(auth()->user(), false);

		return [
			'message' => trans('notifications::notifications.headerNotificationsVisible')
		];
	}

	public function hide()
	{
		session()->put('header.notifications.visible', false);

		return [
			'message' => trans('notifications::notifications.headerNotificationsNotVisible')
		];
	}

	public function hidePermanently()
	{
		\IlBronza\Notifications\NotificationPreference::setHeaderHidden(auth()->user(), true);

		return [
			'message' => trans('notifications::notifications.headerNotificationsNotVisiblePermanently')
		];
	}
}


<?php

namespace IlBronza\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;

class HeaderNotificationVisibilityController extends Controller
{
	public function show()
	{
		session()->put('header.notifications.visible', true);

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
		session()->put('header.notifications.hidePermanently', true);

		return [
			'message' => trans('notifications::notifications.headerNotificationsNotVisiblePermanently')
		];
	}
}


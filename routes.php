<?php

use IlBronza\Notifications\Http\Controllers\HeaderNotificationVisibilityController;
use IlBronza\Notifications\Http\Controllers\NotificationResetCacheController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(
[
	'middleware' => ['web', 'auth'],
	'prefix' => 'notifications-manager',
	'namespace' => 'IlBronza\Notifications\Http\Controllers'
],
function()
{

	Route::group(['prefix' => 'header-notifications'], function()
	{
		Route::post('show', [HeaderNotificationVisibilityController::class, 'show'])->name('notifications.header.show');
		Route::post('hide', [HeaderNotificationVisibilityController::class, 'hide'])->name('notifications.header.hide');
		Route::post('hide-permanently', [HeaderNotificationVisibilityController::class, 'hidePermanently'])->name('notifications.header.hidePermanently');

	});



	Route::get('notifications/reset-header-session-cache', [NotificationResetCacheController::class, 'header'])->name('notifications.header.resetSessionCache');

	Route::get('notifications/archived', 'CrudNotificationController@archived')->name('notifications.archived');

	Route::resource('notifications', 'CrudNotificationController');

});
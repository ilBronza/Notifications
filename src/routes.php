<?php

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
	Route::get('notifications/archived', 'CrudNotificationController@archived')->name('notifications.archived');

	Route::resource('notifications', 'CrudNotificationController');

});
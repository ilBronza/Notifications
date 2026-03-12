<?php

return [

	'headerNotifications' => [
		'sessionKey' => 'headerNotifications'
	],

	'layout' => 'layouts.app',

	'voiceMessages' => [
		'user_model' => \App\Models\User::class,
		'storage_disk' => 'notifications_audio',
		'storage_path' => '',
		'disk_config' => [
			'driver' => 'local',
			'root' => storage_path('app/public/notifications_audio'),
			'url' => env('APP_URL') . '/storage/notifications_audio',
			'visibility' => 'public',
		],
	],

];
<?php

return [
    // Null uses Laravel's configured authentication model.
    'user_model' => null,
    'role_model' => \IlBronza\AccountManager\Models\Role::class,
    'routes' => ['enabled' => true, 'prefix' => 'notifications-manager', 'middleware' => ['web', 'auth']],
    'layout' => 'uikittemplate::app',
    'allow_html' => false,
    'headerNotifications' => ['sessionKey' => 'headerNotifications', 'limit' => 50],
    'scheduled' => ['daysBefore' => 0],
    'voiceMessages' => [
        'user_model' => null,
        // An invokable class receiving the user query and authenticated sender; apply tenant/team limits here.
        'recipient_scope' => null,
        'per_page' => 24,
        'max_kilobytes' => 5120,
        'max_seconds' => 120,
        'storage_disk' => 'notifications_audio_private',
        'storage_path' => '',
        'disk_config' => [
            'driver' => 'local',
            'root' => storage_path('app/private/notifications_audio'),
            'visibility' => 'private',
            'throw' => true,
        ],
    ],
    'backup_slack' => [
        'project_name' => env('BACKUP_SLACK_PROJECT_NAME'),
        'success_webhook_url' => env('BACKUP_SLACK_SUCCESS_WEBHOOK_URL'),
        'failure_webhook_url' => env('BACKUP_SLACK_FAILURE_WEBHOOK_URL'),
    ],
    'speechToText' => [
        'driver' => env('NOTIFICATIONS_SPEECH_TO_TEXT_DRIVER', 'openai_whisper'),
        'timeout' => 60,
        'max_bytes' => 25 * 1024 * 1024,
        // URL downloads require an explicit HTTPS host allowlist. Storage disks work without it.
        'allowed_hosts' => [],
        'openai_whisper' => ['api_key' => env('OPENAI_API_KEY'), 'model' => 'whisper-1'],
    ],
];

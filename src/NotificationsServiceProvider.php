<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\Channels\BackupSlackChannel;
use IlBronza\Notifications\Console\PruneNotifications;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifications.php', 'notifications');
        $this->app->singleton('notification', fn () => new Notification);
        $this->app->singleton('notifications', fn () => new NotificationManager);
    }

    public function boot(): void
    {
        $this->configureBackupSlackChannel();
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'notifications');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'notifications');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        if (config('notifications.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/notifications.php');
        }
        $disk = config('notifications.voiceMessages.storage_disk');
        if (in_array($disk, ['notifications_audio', 'notifications_audio_private'], true) && config("filesystems.disks.{$disk}") === null) {
            config(["filesystems.disks.{$disk}" => config('notifications.voiceMessages.disk_config')]);
        }
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/notifications.php' => config_path('notifications.php')], 'notification.config');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'notification.migrations');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/notifications')], 'notifications.views');
            $this->publishes([__DIR__.'/../resources/lang' => lang_path('vendor/notifications')], 'notifications.translations');
            $this->commands([PruneNotifications::class]);
        }
    }

    private function configureBackupSlackChannel(): void
    {
        $backupNotifications = config('backup.notifications.notifications');

        if (! is_array($backupNotifications)) {
            return;
        }

        $webhookKeys = [
            BackupWasSuccessfulNotification::class => 'success_webhook_url',
            BackupHasFailedNotification::class => 'failure_webhook_url',
        ];

        foreach ($webhookKeys as $notification => $webhookKey) {
            if (! isset($backupNotifications[$notification])
                || ! config('notifications.backup_slack.'.$webhookKey)
                || config('backup.notifications.slack.'.$webhookKey)) {
                continue;
            }

            $channels = array_diff($backupNotifications[$notification], ['slack', BackupSlackChannel::class]);

            $backupNotifications[$notification] = [
                BackupSlackChannel::class,
                ...array_values($channels),
            ];
        }

        config(['backup.notifications.notifications' => $backupNotifications]);
    }

    public function provides() { return ['notification', 'notifications']; }
}

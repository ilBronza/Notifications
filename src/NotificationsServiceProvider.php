<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\Console\PruneNotifications;
use Illuminate\Support\ServiceProvider;

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

    public function provides() { return ['notification', 'notifications']; }
}

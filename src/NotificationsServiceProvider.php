<?php

namespace IlBronza\Notifications;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\NotificationManager;
use IlBronza\Notifications\Observers\ExtendedDatabaseNotificationObserver;
use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'notifications');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'notifications');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        ExtendedDatabaseNotification::observe(ExtendedDatabaseNotificationObserver::class);

        // // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notifications.php', 'notifications');

        // // Register the service the package provides.
        $this->app->singleton('notification', function ($app) {
            return new Notification;
        });

        $this->app->singleton('notifications', function ($app) {
            return new NotificationManager;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['notification'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/notifications.php' => config_path('notifications.php'),
        ], 'notification.config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'notification.migrations');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/ilbronza'),
        ], 'notifications.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ilbronza'),
        ], 'notifications.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ilbronza'),
        ], 'notifications.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

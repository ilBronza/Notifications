<?php

namespace IlBronza\Notifications\Tests;

use IlBronza\AccountManager\AccountManagerServiceProvider;
use IlBronza\AccountManager\Models\User;
use IlBronza\Buttons\ButtonsServiceProvider;
use IlBronza\CRUD\CRUDServiceProvider;
use IlBronza\Datatables\DatatablesServiceProvider;
use IlBronza\Form\FormServiceProvider;
use IlBronza\FormField\FormFieldServiceProvider;
use IlBronza\Menu\MenuServiceProvider;
use IlBronza\Notifications\NotificationsServiceProvider;
use IlBronza\UikitTemplate\UikitTemplate;
use IlBronza\UikitTemplate\UikitTemplateServiceProvider;
use IlBronza\Ukn\UknServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
            AccountManagerServiceProvider::class,
            UknServiceProvider::class,
            MenuServiceProvider::class,
            UikitTemplateServiceProvider::class,
            ButtonsServiceProvider::class,
            FormFieldServiceProvider::class,
            FormServiceProvider::class,
            DatatablesServiceProvider::class,
            CRUDServiceProvider::class,
            NotificationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $storage = sys_get_temp_dir().'/notifications-tests-'.getmypid();
        foreach (['framework/views', 'framework/sessions', 'framework/cache/data', 'logs', 'app'] as $directory) {
            if (! is_dir($storage.'/'.$directory)) { mkdir($storage.'/'.$directory, 0777, true); }
        }
        $app->useStoragePath($storage);
        $app['config']->set('view.compiled', $storage.'/framework/views');
        $app['config']->set('logging.channels.single.path', $storage.'/logs/laravel.log');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('app.locale', 'it');
        $app['config']->set('app.template', UikitTemplate::class);
        $app['config']->set('menu.childrenPerColumn', 8);
        $app['config']->set('menu.serviceProviders', []);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('notifications.user_model', \IlBronza\Notifications\Tests\Fixtures\Recipient::class);
        $app['config']->set('activitylog.enabled', false);
        $app['config']->set('activitylog.default_auth_driver', null);
    }

    protected function defineDatabaseMigrations()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('users')->insert(['id' => 2, 'name' => 'Tester']);
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        require_once __DIR__.'/../database/migrations/2021_07_22_101918_add_custom_fields_to_notifications_table.php';
        require_once __DIR__.'/../database/migrations/2022_11_07_111354_add_schedule_fields_to_notifications_table.php';
        (new \AddCustomFieldsToNotificationsTable)->up();
        (new \AddScheduleFieldsToNotificationsTable)->up();
        (require __DIR__.'/../database/migrations/2026_10_04_000001_create_notification_preferences_table.php')->up();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User(['id' => 2, 'name' => 'Tester', 'email' => 'tester@example.com']);
        $user->setRelation('roles', collect([new Role(['name' => 'superadmin', 'guard_name' => 'web'])]));
        $user->setRelation('userdata', null);
        $this->actingAs($user);
        $this->withoutExceptionHandling();
    }
}

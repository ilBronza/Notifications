<?php

namespace IlBronza\Notifications\Tests\Feature;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Tests\TestCase;

class NotificationCrudRoutesTest extends TestCase
{
    private function createNotification(): ExtendedDatabaseNotification
    {
        return ExtendedDatabaseNotification::create([
            'id' => '04857e19-48d3-4fec-acd9-90d11741cb3f',
            'type' => 'App\\Notifications\\TestNotification',
            'notifiable_type' => \IlBronza\AccountManager\Models\User::class,
            'notifiable_id' => 2,
            'data' => ['message' => 'Messaggio della notifica'],
            'managed' => 'Annotazione di gestione',
        ]);
    }

    public function test_create_renders_with_the_registered_store_action(): void
    {
        $this->get(route('notifications.create'))
            ->assertOk()
            ->assertSee('action="' . route('notifications.store') . '"', false)
            ->assertSee('name="managed"', false);
    }

    public function test_show_renders_the_notification_details(): void
    {
        $notification = $this->createNotification();

        $this->get(route('notifications.show', $notification))
            ->assertOk()
            ->assertSee('Messaggio della notifica')
            ->assertSee('Annotazione di gestione');
    }

    public function test_edit_renders_with_the_registered_update_action(): void
    {
        $notification = $this->createNotification();

        $this->get(route('notifications.edit', $notification))
            ->assertOk()
            ->assertSee('action="' . route('notifications.update', $notification) . '"', false)
            ->assertSee('value="PUT"', false)
            ->assertSee('Annotazione di gestione');
    }
}

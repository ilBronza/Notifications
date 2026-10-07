<?php
namespace IlBronza\Notifications\Tests\Feature;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Notification;
use IlBronza\Notifications\NotificationPreference;
use IlBronza\Notifications\Notifications\AudioNotification;
use IlBronza\Notifications\Tests\Fixtures\Message;
use IlBronza\Notifications\Tests\Fixtures\Recipient;
use IlBronza\Notifications\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class NotificationHttpTest extends TestCase
{
    public function test_other_users_cannot_show_edit_update_or_delete_a_notification(): void
    {
        $recipient = Recipient::create(['name' => 'Private recipient']);
        $id = Notification::users($recipient)->notification(new Message)->send();
        $this->withExceptionHandling();
        $this->get(route('notifications.show', $id))->assertForbidden();
        $this->get(route('notifications.edit', $id))->assertForbidden();
        $this->putJson(route('notifications.update', $id), ['managed' => 'changed'])->assertForbidden();
        $this->deleteJson(route('notifications.destroy', $id))->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $id, 'managed' => null]);
    }

    public function test_owner_can_update_only_management_fields(): void
    {
        $id = (string) \Illuminate\Support\Str::uuid();
        ExtendedDatabaseNotification::create(['id' => $id, 'type' => Message::class, 'data' => ['message' => 'A private message'],
            'notifiable_type' => get_class(auth()->user()), 'notifiable_id' => auth()->id()]);
        $this->putJson(route('notifications.update', $id), ['managed_at' => '1', 'read_at' => 1, 'notifiable_id' => 999, 'data' => ['message' => 'replaced']])->assertOk();
        $notification = ExtendedDatabaseNotification::findOrFail($id);
        $this->assertNotNull($notification->managed_at);
        $this->assertNotNull($notification->read_at);
        $this->assertSame('A private message', $notification->getMessage());
        $this->assertEquals(2, $notification->notifiable_id);
        $this->assertEquals(2, $notification->managed_by);
    }

    public function test_inbox_is_paginated_and_does_not_expose_storage_or_other_recipients(): void
    {
        $alice = Recipient::create(['name' => 'Alice']); $bob = Recipient::create(['name' => 'Bob']);
        Notification::users($alice)->notification(new Message)->send();
        Notification::users($alice)->notification(new Message)->send();
        Notification::users($bob)->notification(new Message)->send();
        $this->actingAs($alice);
        $response = $this->getJson(route('notifications.inbox', ['per_page' => 1]))->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data');
        $this->assertArrayNotHasKey('parent', $response->json('data.0'));
        $this->assertArrayNotHasKey('notifiable_id', $response->json('data.0'));
        $this->getJson(route('notifications.count'))->assertJson(['unmanaged' => 2, 'unread' => 2]);
    }

    public function test_bulk_update_is_all_or_nothing_when_an_id_belongs_to_another_user(): void
    {
        $alice = Recipient::create(['name' => 'Alice']); $bob = Recipient::create(['name' => 'Bob']);
        $own = Notification::users($alice)->notification(new Message)->send();
        $other = Notification::users($bob)->notification(new Message)->send();
        $this->actingAs($alice)->withExceptionHandling();
        $this->patchJson(route('notifications.bulk'), ['ids' => [$own, $other], 'action' => 'read'])->assertForbidden();
        $this->assertNull(ExtendedDatabaseNotification::find($own)->read_at);
        $this->patchJson(route('notifications.bulk'), ['ids' => [$own], 'action' => 'read'])->assertOk()->assertJsonPath('updated', 1);
        $this->getJson(route('notifications.count'))->assertJsonPath('unread', 0);
    }

    public function test_text_form_creates_a_real_notification(): void
    {
        $recipient = Recipient::create(['name' => 'Alice']);
        $this->postJson(route('notifications.store'), ['user_id' => $recipient->id, 'message' => 'Hello', 'managed' => 'Follow up'])->assertCreated();
        $this->assertSame('Hello', $recipient->getAllNotifications()->sole()->getMessage());
    }

    public function test_audio_is_protected_and_removed_with_the_last_receipt(): void
    {
        Storage::fake('voice-test');
        Storage::disk('voice-test')->put('recording.wav', 'audio-content');
        $alice = Recipient::create(['name' => 'Alice']); $bob = Recipient::create(['name' => 'Bob']);
        $id = Notification::users([$alice, $bob])->notification(AudioNotification::fromStorage('voice-test', 'recording.wav'))->send();
        $this->withExceptionHandling();
        $this->get(route('notifications.audio', $id))->assertForbidden();
        $this->actingAs($alice)->get(route('notifications.audio', $id))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        ExtendedDatabaseNotification::findOrFail($id)->delete();
        Storage::disk('voice-test')->assertExists('recording.wav');
        $child = $bob->getAllNotifications()->sole();
        $this->actingAs($bob)->get(route('notifications.audio', $child))->assertOk();
        $child->delete();
        Storage::disk('voice-test')->assertMissing('recording.wav');
    }

    public function test_upload_stores_private_audio_and_creates_notification(): void
    {
        Storage::fake('voice-test'); config(['notifications.voiceMessages.storage_disk' => 'voice-test']);
        $recipient = Recipient::create(['name' => 'Alice']);
        $audio = UploadedFile::fake()->create('message.wav', 10, 'audio/wav');
        $response = $this->postJson(route('notifications.voice-messages.store'), ['user_id' => (string) $recipient->id, 'audio' => $audio])->assertCreated();
        $notification = ExtendedDatabaseNotification::findOrFail($response->json('id'));
        $this->assertArrayNotHasKey('audio_url', $notification->data);
        Storage::disk('voice-test')->assertExists($notification->data['audio_path']);
        $this->assertSame('private', Storage::disk('voice-test')->getVisibility($notification->data['audio_path']));
    }

    public function test_rejected_delivery_cleans_up_the_uploaded_file(): void
    {
        Storage::fake('voice-test'); config(['notifications.voiceMessages.storage_disk' => 'voice-test']);
        $recipient = Recipient::create(['name' => 'Alice']);
        Event::listen(NotificationSending::class, fn () => false);
        try {
            $this->postJson(route('notifications.voice-messages.store'), ['user_id' => (string) $recipient->id, 'audio' => UploadedFile::fake()->create('message.wav', 10, 'audio/wav')]);
            $this->fail('Expected delivery to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame([], Storage::disk('voice-test')->allFiles());
            $this->assertSame(0, ExtendedDatabaseNotification::count());
        }
    }

    public function test_send_gate_rejects_audio_before_storage(): void
    {
        Storage::fake('voice-test'); config(['notifications.voiceMessages.storage_disk' => 'voice-test']);
        $recipient = Recipient::create(['name' => 'Alice']);
        $this->actingAs($recipient);
        Gate::define('notifications.sendTo', fn ($user, $target) => false);
        $this->withExceptionHandling();
        $this->postJson(route('notifications.voice-messages.store'), ['user_id' => (string) $recipient->id, 'audio' => UploadedFile::fake()->create('message.wav', 10, 'audio/wav')])->assertForbidden();
        $this->assertSame([], Storage::disk('voice-test')->allFiles());
    }

    public function test_voice_page_searches_and_paginates(): void
    {
        Recipient::create(['name' => 'Alice']); Recipient::create(['name' => 'Bob']);
        config(['notifications.voiceMessages.per_page' => 1]);
        $this->get(route('notifications.voice-messages.index', ['q' => 'Alice']))->assertOk()->assertSee('Alice')->assertDontSee('Bob');
    }

    public function test_html_is_escaped_by_default(): void
    {
        $notification = new ExtendedDatabaseNotification(['data' => ['message' => '<script>alert(1)</script>']]);
        $html = $notification->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_permanent_header_preference_survives_session_reset_and_can_be_restored(): void
    {
        $user = auth()->user();
        $this->postJson(route('notifications.header.hidePermanently'))->assertOk();
        session()->flush();
        $this->assertTrue(NotificationPreference::hiddenFor($user));
        $this->postJson(route('notifications.header.show'))->assertOk();
        $this->assertFalse(NotificationPreference::hiddenFor($user));
    }

    public function test_inbox_pages_render_and_paginate(): void
    {
        for ($index = 0; $index < 26; $index++) {
            ExtendedDatabaseNotification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(), 'type' => Message::class, 'data' => ['message' => 'Inbox entry '.$index],
                'notifiable_type' => get_class(auth()->user()), 'notifiable_id' => auth()->id(),
            ]);
        }
        $this->get(route('notifications.index'))->assertOk()->assertSee('inbox-bulk', false)->assertSee('page=2', false);
        $this->get(route('notifications.archived'))->assertOk()->assertSee('Nessuna notifica');
    }

    public function test_guest_cannot_read_the_inbox_or_submit_updates(): void
    {
        auth()->logout(); $this->withExceptionHandling();
        $this->getJson(route('notifications.inbox'))->assertUnauthorized();
        $this->patchJson(route('notifications.bulk'), [])->assertUnauthorized();
    }

    public function test_invalid_audio_is_rejected_without_files(): void
    {
        Storage::fake('voice-test'); config(['notifications.voiceMessages.storage_disk' => 'voice-test']);
        $recipient = Recipient::create(['name' => 'Alice']);
        $this->withExceptionHandling();
        $this->postJson(route('notifications.voice-messages.store'), ['user_id' => $recipient->id, 'audio' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')])->assertUnprocessable();
        $this->postJson(route('notifications.voice-messages.store'), ['user_id' => [$recipient->id], 'audio' => UploadedFile::fake()->create('audio.wav', 10, 'audio/wav')])->assertUnprocessable();
        $this->assertSame([], Storage::disk('voice-test')->allFiles());
    }
}

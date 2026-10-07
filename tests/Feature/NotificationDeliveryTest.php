<?php
namespace IlBronza\Notifications\Tests\Feature;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Facades\Notification as Send;
use IlBronza\Notifications\Http\Helpers\NotificationGetterHelper;
use IlBronza\Notifications\Jobs\SendNotificationGroup;
use IlBronza\Notifications\Jobs\SendQueuedNotification;
use IlBronza\Notifications\Notification;
use IlBronza\Notifications\Tests\Fixtures\Message;
use IlBronza\Notifications\Tests\Fixtures\QueuedMessage;
use IlBronza\Notifications\Tests\Fixtures\Recipient;
use IlBronza\Notifications\Tests\Fixtures\Role;
use IlBronza\Notifications\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

class NotificationDeliveryTest extends TestCase
{
    private function recipient(string $name = 'Recipient'): Recipient { return Recipient::create(['name' => $name]); }

    public function test_inbox_is_private_and_works_without_roles(): void
    {
        $alice = $this->recipient('Alice'); $bob = $this->recipient('Bob');
        $own = Notification::users($alice)->notification(new Message)->send();
        Notification::users($bob)->notification(new Message)->send();
        $this->assertSame([$own], $alice->getAllNotifications()->modelKeys());
        $this->assertFalse(ExtendedDatabaseNotification::find($own)->canBeAccessedBy($bob));
        $this->assertTrue(ExtendedDatabaseNotification::find($own)->canBeAccessedBy($alice));
        $role = Role::create(['name' => 'operators']);
        Notification::roles($role)->notification(new Message)->send();
        $this->assertCount(1, $role->getAllNotifications());
    }

    public function test_builder_resolves_names_ids_models_and_deduplicates(): void
    {
        config(['notifications.user_model' => Recipient::class, 'notifications.role_model' => Role::class]);
        $alice = $this->recipient('Alice');
        $this->assertTrue(Notification::parseUsers($alice->id)->first()->is($alice));
        $this->assertTrue(Notification::parseUsers('Alice')->first()->is($alice));
        $this->assertTrue(Notification::parseUsers(['Alice'])->first()->is($alice));
        $builder = Notification::users(collect([$alice, $alice]))->notification(new Message);
        $id = $builder->send();
        $this->assertIsString($id);
        $this->assertSame(1, ExtendedDatabaseNotification::count());
        $this->assertTrue($builder->shiftUser()->is($alice));
    }

    public function test_group_inherits_content_and_shares_management_but_not_read_state(): void
    {
        $alice = $this->recipient('Alice'); $bob = $this->recipient('Bob');
        $rootId = Notification::users([$alice, $bob])->notification(new Message)->send();
        $root = ExtendedDatabaseNotification::findOrFail($rootId);
        $child = $bob->getAllNotifications()->sole();
        $this->assertSame('A private message', $child->getMessage());
        $child->markAsRead();
        $this->assertNull($root->fresh()->read_at);
        $child->update(['managed_at' => true, 'managed' => 'Done']);
        $this->assertNotNull($root->fresh()->managed_at);
        $this->assertEquals(2, $root->fresh()->managed_by);
        $this->assertSame(0, $alice->getNotificationsCount());
        $this->assertSame('Done', $root->fresh()->managed);
    }

    public function test_direct_receipt_is_preferred_over_role_receipt_with_morph_maps(): void
    {
        Relation::morphMap(['person' => Recipient::class, 'team' => Role::class]);
        try {
            $alice = $this->recipient('Alice'); $role = Role::create(['name' => 'operators']);
            $alice->roles()->attach($role);
            Notification::roles($role)->users($alice)->notification(new Message)->send();
            $inbox = $alice->getAllNotifications();
            $this->assertCount(1, $inbox);
            $this->assertSame('person', $inbox->first()->notifiable_type);
            $this->assertSame(1, $alice->getNotificationsCount());
        } finally { Relation::morphMap([], false); }
    }

    public function test_group_is_rolled_back_when_a_child_is_rejected(): void
    {
        $alice = $this->recipient(); $bob = $this->recipient();
        Event::listen(NotificationSending::class, fn ($event) => $event->notifiable->is($bob) ? false : null);
        try {
            Notification::users([$alice, $bob])->notification(new Message)->send();
            $this->fail('Expected group delivery to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(0, ExtendedDatabaseNotification::count());
        }
    }

    public function test_queued_group_is_atomic_and_retry_safe(): void
    {
        Bus::fake();
        $alice = $this->recipient(); $bob = $this->recipient();
        $id = Notification::users([$alice, $bob])->notification(new QueuedMessage)->send();
        $this->assertSame(0, ExtendedDatabaseNotification::count());
        Bus::assertDispatched(SendNotificationGroup::class, function ($job) use ($id) {
            $job = unserialize(serialize($job));
            $job->handle(app(ChannelManager::class));
            $job->handle(app(ChannelManager::class));
            $this->assertSame(2, ExtendedDatabaseNotification::count());
            $this->assertSame(1, ExtendedDatabaseNotification::where('parent_id', $id)->count());
            return true;
        });
    }

    public function test_queued_single_keeps_extended_payload_and_returns_an_id(): void
    {
        Bus::fake();
        $notification = new QueuedMessage; $notification->link = '/example';
        $id = Notification::users($this->recipient())->notification($notification)->send();
        $this->assertIsString($id);
        Bus::assertDispatched(SendQueuedNotification::class, function ($job) use ($id) {
            $job = unserialize(serialize($job));
            $job->handle(app(ChannelManager::class));
            $this->assertSame('/example', ExtendedDatabaseNotification::findOrFail($id)->link);
            return true;
        });
    }

    public function test_facade_accepts_multiple_recipients(): void
    {
        $ids = Send::send([$this->recipient(), $this->recipient()], new Message);
        $this->assertCount(2, $ids);
        $this->assertSame(2, ExtendedDatabaseNotification::count());
    }

    public function test_deleting_root_preserves_other_recipients_content(): void
    {
        $alice = $this->recipient(); $bob = $this->recipient(); $carol = $this->recipient();
        $id = Notification::users([$alice, $bob, $carol])->notification(new Message)->send();
        ExtendedDatabaseNotification::findOrFail($id)->delete();
        $this->assertCount(0, $alice->getAllNotifications());
        $this->assertSame('A private message', $bob->getAllNotifications()->sole()->getMessage());
        $this->assertSame('A private message', $carol->getAllNotifications()->sole()->getMessage());
    }

    public function test_empty_recipients_fail_before_delivery(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Notification::users([])->notification(new Message)->send();
    }

    public function test_header_honors_roles_dates_priority_and_changes_without_session_staleness(): void
    {
        $alice = $this->recipient(); $role = Role::create(['name' => 'operators']);
        $alice->roles()->attach($role); $this->actingAs($alice);
        $id = Notification::roles($role)->notification(new Message)->send();
        $model = ExtendedDatabaseNotification::findOrFail($id);
        $model->update(['headerbar' => true, 'valid_from' => now()->subMinute(), 'priority' => 0]);
        $this->assertCount(1, NotificationGetterHelper::getHeaderNotifications());
        $this->assertCount(1, NotificationGetterHelper::getRolesHeaderNotifications());
        $model->markAsRead();
        $this->assertCount(0, NotificationGetterHelper::getHeaderNotifications());
        $model->markAsUnread();
        $model->update(['valid_to' => now()->subMinute()]);
        $this->assertCount(0, NotificationGetterHelper::getHeaderNotifications());
    }

    public function test_laravel_notify_methods_keep_the_extended_database_channel(): void
    {
        $recipient = $this->recipient();
        $message = new Message;
        $message->link = '/details';
        $id = $recipient->notify($message);
        $this->assertSame('/details', ExtendedDatabaseNotification::findOrFail($id)->link);
        $this->assertEquals(2, ExtendedDatabaseNotification::findOrFail($id)->created_by);
    }

    public function test_legacy_children_used_by_schedules_inherit_content_and_link(): void
    {
        $first = Role::create(['name' => 'first']); $second = Role::create(['name' => 'second']);
        $message = new Message; $message->link = '/schedule';
        $root = Send::send($first, $message);
        $id = Send::send($second, new \IlBronza\Notifications\ChildDatabaseNotification($root));
        $child = ExtendedDatabaseNotification::findOrFail($id);
        $this->assertSame($root, $child->parent_id);
        $this->assertSame('A private message', $child->getMessage());
        $this->assertSame('/schedule', $child->getLink());
        $this->assertSame('Message', $child->getType());
    }

    public function test_scheduled_metadata_survives_queued_group_delivery(): void
    {
        Bus::fake();
        $date = now()->addDay()->startOfSecond();
        $message = (new \IlBronza\Notifications\Tests\Fixtures\ScheduledMessage)->scheduledFor($date);
        Notification::users([$this->recipient(), $this->recipient()])->notification($message)->send();
        Bus::assertDispatched(SendNotificationGroup::class, function ($job) use ($date) {
            unserialize(serialize($job))->handle(app(ChannelManager::class));
            foreach (ExtendedDatabaseNotification::all() as $notification) {
                $this->assertTrue($notification->scheduled_for->equalTo($date));
                $this->assertTrue($notification->valid_from->equalTo($date));
                $this->assertSame(0, $notification->priority);
                $this->assertTrue($notification->headerbar);
            }
            return true;
        });
    }

    public function test_queue_dispatch_waits_for_commit_and_is_discarded_on_rollback(): void
    {
        \Illuminate\Support\Facades\Schema::create('jobs', function ($table) {
            $table->bigIncrements('id'); $table->string('queue'); $table->longText('payload');
            $table->unsignedTinyInteger('attempts'); $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at'); $table->unsignedInteger('created_at');
        });
        config(['queue.default' => 'database', 'queue.connections.database' => [
            'driver' => 'database', 'connection' => 'testing', 'table' => 'jobs', 'queue' => 'default', 'retry_after' => 90,
        ]]);
        $recipient = $this->recipient();
        \Illuminate\Support\Facades\DB::beginTransaction();
        $recipient->notify(new QueuedMessage);
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('jobs')->count());
        \Illuminate\Support\Facades\DB::rollBack();
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('jobs')->count());
        \Illuminate\Support\Facades\DB::beginTransaction();
        $recipient->notify(new QueuedMessage);
        \Illuminate\Support\Facades\DB::commit();
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('jobs')->count());
    }

    public function test_explicit_id_for_multiple_recipients_fails_before_writing(): void
    {
        $message = new Message; $message->id = (string) \Illuminate\Support\Str::uuid();
        try {
            Send::send([$this->recipient(), $this->recipient()], $message);
            $this->fail('Expected invalid recipient count.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(0, ExtendedDatabaseNotification::count());
        }
    }

    public function test_cancelled_notification_does_not_return_a_delivery_id(): void
    {
        Event::listen(NotificationSending::class, fn () => false);
        $ids = Send::send($this->recipient(), new Message);
        $this->assertCount(0, $ids);
        $this->assertSame(0, ExtendedDatabaseNotification::count());
    }
}

<?php
namespace IlBronza\Notifications\Tests\Feature;

use IlBronza\Notifications\ExtendedDatabaseNotification;
use IlBronza\Notifications\Notification;
use IlBronza\Notifications\Tests\Fixtures\Message;
use IlBronza\Notifications\Tests\Fixtures\Recipient;
use IlBronza\Notifications\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NotificationMaintenanceTest extends TestCase
{
    public function test_migrations_can_roll_back_without_deleting_unrelated_tables(): void
    {
        Schema::create('notifiables', fn (Blueprint $table) => $table->id());
        $preferences = require __DIR__.'/../../database/migrations/2026_10_04_000001_create_notification_preferences_table.php';
        $preferences->down();
        (new \AddScheduleFieldsToNotificationsTable)->down();
        (new \AddCustomFieldsToNotificationsTable)->down();
        $this->assertTrue(Schema::hasTable('notifiables'));
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertFalse(Schema::hasColumn('notifications', 'parent_id'));
        (new \AddCustomFieldsToNotificationsTable)->up();
        (new \AddScheduleFieldsToNotificationsTable)->up();
        $preferences->up();
        $this->assertTrue(Schema::hasColumn('notifications', 'managed_at'));
    }

    public function test_pruning_preserves_unmanaged_recent_and_partially_managed_groups(): void
    {
        $alice = Recipient::create(['name' => 'Alice']); $bob = Recipient::create(['name' => 'Bob']);
        $old = Notification::users($alice)->notification(new Message)->send();
        ExtendedDatabaseNotification::find($old)->update(['managed_at' => now()->subDays(120)]);
        $new = Notification::users($alice)->notification(new Message)->send();
        ExtendedDatabaseNotification::find($new)->update(['managed_at' => now()]);
        $group = Notification::users([$alice, $bob])->notification(new Message)->send();
        ExtendedDatabaseNotification::find($group)->update(['managed_at' => now()->subDays(120)]);
        ExtendedDatabaseNotification::where('parent_id', $group)->update(['managed_at' => null]);
        $this->artisan('notifications:prune', ['--days' => 90, '--dry-run' => true])->expectsOutput('Would remove: 1')->assertSuccessful();
        $this->assertSame(4, ExtendedDatabaseNotification::count());
        $this->artisan('notifications:prune', ['--days' => 90])->expectsOutput('Removed: 1')->assertSuccessful();
        $this->assertSame(3, ExtendedDatabaseNotification::count());
        $this->assertNotNull(ExtendedDatabaseNotification::find($group));
    }
}

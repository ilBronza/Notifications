<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->string('notifiable_id', 64);
            $table->boolean('header_hidden')->default(false);
            $table->timestamps();
            $table->unique(['notifiable_type', 'notifiable_id'], 'notification_preferences_recipient_unique');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'managed_at'], 'notifications_recipient_managed_index');
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_recipient_read_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_recipient_managed_index');
            $table->dropIndex('notifications_recipient_read_index');
        });
        Schema::dropIfExists('notification_preferences');
    }
};

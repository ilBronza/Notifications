<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldsToNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table)
        {
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('notifications');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');

            $table->unsignedBigInteger('managed_by')->nullable();
            $table->foreign('managed_by')->references('id')->on('users');
            $table->timestamp('managed_at')->nullable();
            $table->text('managed')->nullable();

            $table->string('link', 512)->nullable();
            $table->string('link_text', 512)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table)
        {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['managed_by']);

            $table->dropColumn('parent_id');
            $table->dropColumn('created_by');
            $table->dropColumn('managed_by');

            $table->dropColumn('managed_at');
            $table->dropColumn('managed');
            $table->dropColumn('link');
            $table->dropColumn('link_text');
        });
    }
}

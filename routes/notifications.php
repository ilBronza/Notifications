<?php

use IlBronza\Notifications\Http\Controllers\CrudNotificationController;
use IlBronza\Notifications\Http\Controllers\HeaderNotificationVisibilityController;
use IlBronza\Notifications\Http\Controllers\InboxController;
use IlBronza\Notifications\Http\Controllers\NotificationResetCacheController;
use IlBronza\Notifications\Http\Controllers\VoiceMessageController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => config('notifications.routes.middleware', ['web', 'auth']),
    'prefix' => config('notifications.routes.prefix', 'notifications-manager'),
], function () {
    Route::post('header-notifications/show', [HeaderNotificationVisibilityController::class, 'show'])->name('notifications.header.show');
    Route::post('header-notifications/hide', [HeaderNotificationVisibilityController::class, 'hide'])->name('notifications.header.hide');
    Route::post('header-notifications/hide-permanently', [HeaderNotificationVisibilityController::class, 'hidePermanently'])->name('notifications.header.hidePermanently');
    Route::post('notifications/reset-header-session-cache', [NotificationResetCacheController::class, 'header'])->name('notifications.header.resetSessionCache');
    Route::get('inbox', [InboxController::class, 'index'])->name('notifications.inbox');
    Route::get('inbox/count', [InboxController::class, 'count'])->name('notifications.count');
    Route::patch('inbox', [InboxController::class, 'update'])->name('notifications.bulk');
    Route::get('notifications/archived', [CrudNotificationController::class, 'archived'])->name('notifications.archived');
    Route::get('notifications/{notification}/audio', [VoiceMessageController::class, 'audio'])->name('notifications.audio');
    Route::resource('notifications', CrudNotificationController::class);
    Route::get('voice-messages', [VoiceMessageController::class, 'index'])->name('notifications.voice-messages.index');
    Route::post('voice-messages', [VoiceMessageController::class, 'store'])->middleware('throttle:30,1')->name('notifications.voice-messages.store');
});

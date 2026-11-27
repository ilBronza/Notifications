<?php

namespace IlBronza\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Throwable;

class BackupSlackChannel
{
    public function send(
        object $notifiable,
        BackupHasFailedNotification|BackupWasSuccessfulNotification $notification,
    ): void {
        $successful = $notification instanceof BackupWasSuccessfulNotification;
        $webhookKey = $successful ? 'success_webhook_url' : 'failure_webhook_url';
        $webhook = config('notifications.backup_slack.'.$webhookKey);
        $outcome = $successful ? 'Backup completato' : 'Backup fallito';
        $projectName = config('notifications.backup_slack.project_name') ?: $notification->applicationName();
        $project = htmlspecialchars($projectName, ENT_NOQUOTES, 'UTF-8');

        try {
            $response = Http::asJson()
                ->connectTimeout(3)
                ->timeout(6)
                ->withoutRedirecting()
                ->post($webhook, [
                    'text' => $outcome.' — '.$project,
                ]);
        } catch (Throwable $exception) {
            Log::channel('single')->warning('Invio della notifica Slack del backup fallito.', [
                'error_type' => $exception::class,
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::channel('single')->warning('Invio della notifica Slack del backup fallito.', [
                'status' => $response->status(),
            ]);
        }
    }
}

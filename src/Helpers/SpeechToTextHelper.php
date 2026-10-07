<?php

namespace IlBronza\Notifications\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SpeechToTextHelper
{
    // Null remains the public failure result. Details are recorded without audio contents or credentials.
    public static function transcribe(string $audioPath, ?string $disk = null, ?string $language = null): ?string
    {
        if (config('notifications.speechToText.driver') !== 'openai_whisper') {
            Log::warning('Notifications: unsupported transcription driver.');
            return null;
        }
        return static::transcribeWithOpenAIWhisper($audioPath, $disk, $language);
    }

    protected static function transcribeWithOpenAIWhisper(string $audioPath, ?string $disk, ?string $language): ?string
    {
        $key = config('notifications.speechToText.openai_whisper.api_key');
        if (! $key) { return null; }
        $file = null; $path = null; $temporary = false;
        try {
            [$path, $temporary] = static::resolveFilePath($audioPath, $disk);
            if (! $path || ! is_file($path) || filesize($path) > config('notifications.speechToText.max_bytes', 26214400)) {
                return null;
            }
            $file = fopen($path, 'rb');
            if ($file === false) { return null; }
            $params = ['model' => config('notifications.speechToText.openai_whisper.model', 'whisper-1'), 'response_format' => 'text'];
            if ($language !== null) { $params['language'] = $language; }
            $filename = basename(parse_url($audioPath, PHP_URL_PATH) ?: $audioPath);
            $response = Http::withToken($key)->connectTimeout(10)->timeout(config('notifications.speechToText.timeout', 60))
                ->attach('file', $file, $filename)->post('https://api.openai.com/v1/audio/transcriptions', $params);
            if ($response->successful()) { return trim($response->body()); }
            Log::warning('Notifications: transcription request failed.', ['status' => $response->status()]);
            return null;
        } catch (Throwable $exception) {
            Log::warning('Notifications: transcription failed.', ['exception' => get_class($exception)]);
            return null;
        } finally {
            if (is_resource($file)) { fclose($file); }
            if ($temporary && $path && is_file($path)) { unlink($path); }
        }
    }

    protected static function resolveFilePath(string $audioPath, ?string $disk): array
    {
        if ($disk !== null) {
            $storage = Storage::disk($disk);
            if (! $storage->exists($audioPath) || $storage->size($audioPath) > config('notifications.speechToText.max_bytes', 26214400)) {
                return [null, false];
            }
            return static::copyStream($storage->readStream($audioPath));
        }
        if (filter_var($audioPath, FILTER_VALIDATE_URL)) {
            $host = strtolower(parse_url($audioPath, PHP_URL_HOST) ?? '');
            if (parse_url($audioPath, PHP_URL_SCHEME) !== 'https' || ! in_array($host, config('notifications.speechToText.allowed_hosts', []), true)) {
                throw new RuntimeException('Audio URL host is not allowed.');
            }
            $response = Http::connectTimeout(10)->timeout(config('notifications.speechToText.timeout', 60))
                ->withOptions(['stream' => true, 'allow_redirects' => false])->get($audioPath);
            if (! $response->successful()) { return [null, false]; }
            return static::copyStream($response->toPsrResponse()->getBody()->detach());
        }
        return [is_file($audioPath) ? $audioPath : null, false];
    }

    private static function copyStream($stream): array
    {
        if (! is_resource($stream)) { return [null, false]; }
        $path = tempnam(sys_get_temp_dir(), 'notification_audio_');
        $output = null;
        try {
            $output = fopen($path, 'wb');
            $limit = (int) config('notifications.speechToText.max_bytes', 26214400);
            $bytes = stream_copy_to_stream($stream, $output, $limit + 1);
            if ($bytes === false || $bytes > $limit) { throw new RuntimeException('Audio exceeds the size limit.'); }
            return [$path, true];
        } catch (Throwable $exception) {
            if ($path && is_file($path)) { unlink($path); }
            throw $exception;
        } finally {
            fclose($stream);
            if (is_resource($output)) { fclose($output); }
        }
    }
}

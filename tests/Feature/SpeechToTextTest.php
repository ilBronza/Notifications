<?php
namespace IlBronza\Notifications\Tests\Feature;

use IlBronza\Notifications\Helpers\SpeechToTextHelper;
use IlBronza\Notifications\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SpeechToTextTest extends TestCase
{
    public function test_storage_stream_is_transcribed_without_a_local_disk_path(): void
    {
        config(['notifications.speechToText.openai_whisper.api_key' => 'test-key']);
        Storage::fake('remote-audio'); Storage::disk('remote-audio')->put('recording.wav', 'audio');
        Http::fake(function ($request) {
            $body = $request->body();
            $this->assertStringContainsString('name="language"', $body);
            $this->assertStringContainsString('whisper-1', $body);
            return Http::response(' Testo trascritto ', 200);
        });
        $this->assertSame('Testo trascritto', SpeechToTextHelper::transcribe('recording.wav', 'remote-audio', 'it'));
        Http::assertSentCount(1);
    }

    public function test_missing_key_does_not_send_any_request(): void
    {
        config(['notifications.speechToText.openai_whisper.api_key' => null]); Http::fake();
        $this->assertNull(SpeechToTextHelper::transcribe('unused.wav'));
        Http::assertNothingSent();
    }

    public function test_unapproved_remote_url_is_not_fetched(): void
    {
        config(['notifications.speechToText.openai_whisper.api_key' => 'test-key']); Http::fake();
        $this->assertNull(SpeechToTextHelper::transcribe('https://unapproved.example/audio.wav'));
        Http::assertNothingSent();
    }

    public function test_failed_api_and_oversized_audio_return_null(): void
    {
        config(['notifications.speechToText.openai_whisper.api_key' => 'test-key']);
        Storage::fake('voice-test'); Storage::disk('voice-test')->put('audio.wav', 'audio');
        Http::fake(['api.openai.com/*' => Http::response('unavailable', 503)]);
        $this->assertNull(SpeechToTextHelper::transcribe('audio.wav', 'voice-test'));
        config(['notifications.speechToText.max_bytes' => 1]); Http::fake();
        $this->assertNull(SpeechToTextHelper::transcribe('audio.wav', 'voice-test'));
        Http::assertNothingSent();
    }
}

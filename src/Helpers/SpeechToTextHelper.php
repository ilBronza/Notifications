<?php

namespace IlBronza\Notifications\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SpeechToTextHelper
{
    /**
     * Trascrive un file audio in testo.
     *
     * @param string $audioPath Percorso del file (path assoluto, path relativo allo storage, o URL)
     * @param string|null $disk Disk di storage se $audioPath è relativo
     * @param string|null $language Codice lingua (es. 'it' per italiano) per migliorare la trascrizione
     * @return string|null Testo trascritto o null in caso di errore
     */
    public static function transcribe(string $audioPath, ?string $disk = null, ?string $language = null): ?string
    {
        $driver = config('notifications.speechToText.driver');

        return match ($driver) {
            'openai_whisper' => static::transcribeWithOpenAIWhisper($audioPath, $disk, $language),
            default => null,
        };
    }

    /**
     * Trascrizione tramite OpenAI Whisper API.
     */
    protected static function transcribeWithOpenAIWhisper(string $audioPath, ?string $disk, ?string $language): ?string
    {
        $apiKey = config('notifications.speechToText.openai_whisper.api_key');

        if (empty($apiKey)) {
            return null;
        }

        [$filePath, $isTemp] = static::resolveFilePath($audioPath, $disk);

        if ($filePath === null || ! file_exists($filePath)) {
            return null;
        }

        $params = [
            'model' => 'whisper-1',
            'response_format' => 'text',
        ];

        if ($language !== null) {
            $params['language'] = $language;
        }

        try {
            $file = fopen($filePath, 'r');

            if ($file === false) {
                return null;
            }

            try {
                $response = Http::withToken($apiKey)
                    ->attach('file', $file, basename($filePath))
                    ->post('https://api.openai.com/v1/audio/transcriptions', $params);

                if ($response->successful()) {
                    return trim($response->body());
                }

                return null;
            } finally {
                fclose($file);
                if ($isTemp && file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        } catch (\Throwable $e) {
            if ($isTemp && isset($filePath) && file_exists($filePath)) {
                @unlink($filePath);
            }

            return null;
        }
    }

    /**
     * Risolve il percorso del file in un path assoluto.
     * Ritorna [path, isTemp] dove isTemp indica se il file va eliminato dopo l'uso.
     *
     * @return array{0: string|null, 1: bool}
     */
    protected static function resolveFilePath(string $audioPath, ?string $disk): array
    {
        if (filter_var($audioPath, FILTER_VALIDATE_URL)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_');
            $content = @file_get_contents($audioPath);

            if ($content === false) {
                return [null, false];
            }

            file_put_contents($tempFile, $content);

            return [$tempFile, true];
        }

        if ($disk !== null) {
            return [Storage::disk($disk)->path($audioPath), false];
        }

        return [$audioPath, false];
    }
}

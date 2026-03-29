# Notifications

## Messaggi vocali

Il package include una funzionalità per inviare messaggi vocali come notifiche:

1. **Schermata utenti**: `/notifications-manager/voice-messages` - lista di tutti gli utenti
2. **Registrazione**: clic su un utente apre un modal per registrare un messaggio vocale
3. **Invio**: il messaggio viene salvato e inviato come notifica audio al destinatario

### Configurazione

Pubblica la config e personalizza in `config/notifications.php`:

```php
'voiceMessages' => [
    'user_model' => \App\Models\User::class,  // Modello User dell'app
    'storage_disk' => 'notifications_audio',  // Disk dedicato, personalizzabile per progetto
    'storage_path' => '',  // Sottocartella nel disk (vuoto = root del disk)
    'disk_config' => [  // Config del disk se non definito in config/filesystems.php
        'driver' => 'local',
        'root' => storage_path('app/public/notifications_audio'),
        'url' => env('APP_URL') . '/storage/notifications_audio',
        'visibility' => 'public',
    ],
],
'layout' => 'layouts.app',  // Layout per la pagina messaggi vocali
```

Per usare S3 o un altro driver, definisci il disk `notifications_audio` in `config/filesystems.php`:

```php
'notifications_audio' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
],
```

### Speech-to-text (trascrizione)

Helper esterno per trascrivere le note vocali. Chiamabile quando serve:

```php
use IlBronza\Notifications\Helpers\SpeechToTextHelper;

// Da path assoluto
$testo = SpeechToTextHelper::transcribe('/path/to/audio.webm');

// Da path relativo allo storage
$testo = SpeechToTextHelper::transcribe('file.webm', 'notifications_audio');

// Da URL
$testo = SpeechToTextHelper::transcribe('https://example.com/audio.webm');

// Con lingua specifica (es. italiano)
$testo = SpeechToTextHelper::transcribe('file.webm', 'notifications_audio', 'it');
```

Configura in `.env`:
```
OPENAI_API_KEY=sk-...
NOTIFICATIONS_SPEECH_TO_TEXT_DRIVER=openai_whisper
```

### Requisiti

- Eseguire `php artisan storage:link` per rendere accessibili i file audio (con disk locale)
- Il modello User deve implementare `ExtendedNotifiable` per ricevere le notifiche

---

remember to create the artisan command

migrate 
add     use ExtendedNotifiable; to AccountManager User and Role

# Notifications

Notifiche Laravel con destinatari utenti/ruoli, gestione condivisa, inbox paginata,
azioni multiple, avvisi nell'header e messaggi vocali privati.

## Installazione

Requisiti dichiarati: PHP 8.2+, Laravel con minimo dichiarato in Composer
e package IlBronza 2.5+.

I vincoli Composer delle dipendenze Laravel e delle relative integrazioni indicano soltanto una versione minima, senza un limite sulle major successive. La scelta delle versioni concrete spetta all’applicazione ospite e al suo `composer.lock`; la compatibilità del codice va verificata con i test.
Il CRUD e il layout predefinito usano l'ecosistema IlBronza; il package non è
un sostituto indipendente di CRUD/AccountManager/UikitTemplate.

```bash
composer require ilbronza/notifications
php artisan vendor:publish --tag=notification.config
```

Prima delle migrazioni devono esistere `users` e la tabella Laravel `notifications`.
Se quest'ultima manca, generare la migrazione database delle notifiche Laravel
prima di eseguire le migrazioni del package. Lo schema incluso assume `users.id`
intero e `notifications.id` UUID; applicazioni con altri schemi devono adattare
le migrazioni prima dell'installazione.

```bash
php artisan migrate
```

Il service provider è registrato tramite auto-discovery. Non occorre pubblicare
le migrazioni per eseguirle. Configurare il modello utente, oppure lasciare
`notifications.user_model` a `null` per usare `auth.providers.users.model`.

Nel modello destinatario usare `ExtendedNotifiable` al posto di `Notifiable`:

```php
use IlBronza\Notifications\Traits\ExtendedNotifiable;

class User extends Authenticatable
{
    use ExtendedNotifiable;
}
```

Il trait può essere usato anche sui ruoli. La relazione `roles()` è opzionale;
quando presente, l'inbox include i ruoli del destinatario. I morph map Laravel
sono rispettati. Il modello dei ruoli è configurabile con `notifications.role_model`.

## Invio

```php
use IlBronza\Notifications\Notification;
use IlBronza\Notifications\Notifications\TextNotification;

$id = Notification::users($user)
    ->notification(new TextNotification('Documento pronto'))
    ->send();

$id = Notification::roles('administrator')
    ->users($users)
    ->notification(new TextNotification('Richiesta da gestire'))
    ->send();

// Con ExtendedNotifiable anche questi metodi mantengono i metadati estesi:
$user->notify(new TextNotification('Documento pronto'));
$user->notifyNow(new TextNotification('Documento pronto'));
```

`users()` e `roles()` accettano un modello, una collection/array di modelli,
un ID intero, un nome oppure array omogenei di ID interi o nomi.
Per chiavi stringa/UUID passare il modello risolto, evitando ambiguità con i nomi.
I destinatari ripetuti vengono eliminati. Destinatari vuoti e notifiche mancanti
producono errori espliciti prima dell'invio.

Per notifiche database con più destinatari, il builder crea un gruppo in una
transazione: un record principale e ricevute collegate. Restituisce l'ID
principale. Ogni destinatario riceve il proprio payload; `ChildDatabaseNotification`
rimane disponibile per i collegamenti legacy, ereditando il contenuto del padre.

Per notifiche con altri canali viene effettuato l'invio a ogni destinatario.
La facade `IlBronza\Notifications\Facades\Notification` restituisce una stringa
per un destinatario e una collection di ID per più destinatari, anche in coda.
L'ID di un invio accodato identifica il tentativo: la consegna richiede un worker.
Usare notifiche nuove per invii nuovi; un ID esplicito identifica una sola ricevuta.

Il canale Slack è opzionale: installare e configurare `laravel/slack-notification-channel`
secondo la [documentazione Laravel](https://laravel.com/docs/12.x/notifications#slack-notifications).
`SlackNotification` mantiene l'API webhook legacy quando disponibile e usa il nuovo
message builder nelle installazioni che espongono soltanto quello. Nessun servizio
Slack viene contattato dall'installazione o dai test del package.

### Lettura e gestione

- `read_at` appartiene alla singola ricevuta. Leggere una copia non legge quella di altri utenti.
- Una ricevuta indirizzata a un ruolo è condivisa dai membri di quel ruolo.
- `managed_at`, `managed_by` e `managed` sono condivisi da tutto il gruppo.
- L'inbox mostra un elemento per gruppo, preferendo la ricevuta personale a quella del ruolo.
- Eliminare una ricevuta conserva le altre; se serve, un figlio diventa il nuovo padre.
- `getNotificationsCount()` e il contatore principale contano gli avvisi da gestire.

### Code

Le notifiche personalizzate possono implementare `ShouldQueue` e usare `Queueable`.
Il package usa job propri per conservare link, mittente, pianificazione e gruppi.
Le operazioni vengono accodate dopo il commit. Le copie database usano ID stabili:
un retry non crea nuove righe né azzera lo stato già salvato.

Sono rispettati connection, queue, delay, middleware, tries, timeout e backoff.
Per un gruppo database viene usato un solo job: eventuali configurazioni dipendenti
dal destinatario sono valutate sul primo destinatario. La transazione di gruppo
presuppone la stessa connessione database dei destinatari e delle notifiche.
L'atomicità riguarda il database, non gli effetti esterni di listener o altri canali.

```bash
php artisan queue:work
```

### Avvisi con una finestra temporale

Estendere `ScheduledNotification`, implementare `toArray()` e impostare la data:

```php
$notification->scheduledFor(now()->addDays(3));
```

Il record viene creato subito. `valid_from`, `valid_to` e `headerbar` governano
la visibilità nell'header; `scheduled.daysBefore` vale zero per impostazione
predefinita. L'inizio è incluso, la fine esclusa. `priority = 0` è valido.
Per rimandare materialmente la consegna usare una notifica in coda con `delay()`.
Le ricorrenze restano responsabilità dello scheduler dell'applicazione o di Schedules.

## Interfaccia, API e autorizzazioni

Prefisso predefinito: `/notifications-manager`. Le route richiedono `web` e `auth`.
Prefisso, middleware e attivazione si configurano in `notifications.routes`.

| Metodo | Percorso relativo | Funzione |
|---|---|---|
| GET | `notifications` | Inbox paginata, 25 elementi, filtro `unread=1` |
| GET | `notifications/archived` | Notifiche gestite |
| GET / POST | `notifications/create` / `notifications` | Creazione di una notifica testuale |
| GET / PUT / DELETE | `notifications/{id}` | Dettaglio, aggiornamento, cancellazione |
| GET | `inbox` | JSON paginato: `state=all\|unread\|unmanaged\|managed`, `per_page` 1–100 |
| GET | `inbox/count` | Contatori `unread` e `unmanaged` aggiornati |
| PATCH | `inbox` | Azione multipla: `ids` (max 100 UUID distinti), `action=read\|unread\|manage\|unmanage` |
| GET / POST | `voice-messages` | Ricerca destinatari e invio audio |
| GET | `notifications/{id}/audio` | Audio accessibile solo a chi può leggere la notifica |
| POST | `header-notifications/show` | Riattiva gli avvisi, anche se nascosti permanentemente |
| POST | `header-notifications/hide` | Nasconde gli avvisi per la sessione |
| POST | `header-notifications/hide-permanently` | Salva la preferenza persistente |

Le operazioni multiple vengono rifiutate interamente se anche un solo ID non
appartiene all'utente o ai suoi ruoli. Gli aggiornamenti HTTP consentono soltanto
`read_at`, `managed_at` e `managed`. L'API restituisce campi espliciti, senza percorsi
storage o copie di altri destinatari.

Per impostazione predefinita ogni utente autenticato può inviare ai destinatari
presenti nella directory. L'applicazione può restringere il comportamento con Gate:

```php
Gate::define('notifications.send', fn ($sender) => $sender->can('send-notifications'));
Gate::define('notifications.sendTo', fn ($sender, $recipient) => $sender->team_id === $recipient->team_id);
// Opzionale: accesso amministrativo esplicito alle singole notifiche.
Gate::define('notifications.manageAll', fn ($user) => $user->isSuperadmin());
```

Per limitare anche ricerca e selezione destinatari, impostare
`notifications.voiceMessages.recipient_scope` a una classe invocabile:

```php
public function __invoke($query, $sender)
{
    return $query->where('team_id', $sender->team_id);
}
```

Il layout predefinito è `uikittemplate::app`; `notifications.layout` lo sostituisce.
Le view fornite usano UIkit, e il modal audio richiede il suo JavaScript.
I messaggi vengono stampati come testo. `allow_html=true` riattiva HTML non filtrato:
usarlo esclusivamente con contenuti già fidati/sanitizzati dall'applicazione.
View e traduzioni possono essere pubblicate con `notifications.views` e
`notifications.translations`. Italiano e inglese sono inclusi.

L'header usa query fresche, limitate a 50 elementi e ordinate per priorità.
La preferenza permanente è salvata nel database; il pulsante “Riattiva avvisi
in alto” nell'inbox permette di annullarla. Il contatore JSON può essere usato
per aggiornare il badge dell'applicazione senza ricaricare la pagina.

## Audio e trascrizione

I nuovi file usano il disk `notifications_audio_private`, sotto
`storage/app/private/notifications_audio`. Non serve `storage:link`.
Per S3 configurare un disk privato e indicarlo in `voiceMessages.storage_disk`.
Il database conserva disk e percorso; il player usa la route autorizzata.
I vecchi payload `audio_url` HTTP(S) restano leggibili.

Il registratore sceglie un formato supportato, chiude il microfono quando il modal
si chiude, impedisce doppi submit e libera le anteprime. Il limite server è 5 MiB,
configurabile; il limite di registrazione nel browser è 120 secondi. Quest'ultimo
è un limite dell'interfaccia, non una verifica della durata del file sul server.
Il browser richiede HTTPS o localhost e il permesso per il microfono.

Un invio fallito elimina il caricamento. Cancellare l'ultima ricevuta elimina
l'audio referenziato; le altre copie continuano a funzionare.

```php
use IlBronza\Notifications\Helpers\SpeechToTextHelper;

$text = SpeechToTextHelper::transcribe('recording.wav', 'notifications_audio_private', 'it');
```

Impostare `OPENAI_API_KEY`. La trascrizione è esplicita: l'invio audio non richiama
automaticamente servizi esterni. Vengono usati stream anche per storage remoti,
con timeout, limite di dimensione e pulizia dei temporanei. In caso di errore
ritorna `null` e registra il tipo di problema senza contenuti audio o credenziali.
Gli URL richiedono HTTPS e un host presente in `speechToText.allowed_hosts`;
i redirect non vengono seguiti. I percorsi locali devono provenire da codice fidato.

## Conservazione

```bash
php artisan notifications:prune --days=90 --dry-run
php artisan notifications:prune --days=90
```

Il comando elimina solo gruppi interamente gestiti da più di N giorni e i loro
allegati non più referenziati. Non viene schedulato automaticamente. I file esterni
identificati da un vecchio `audio_url` non vengono eliminati dal package.

## Aggiornamento da versioni precedenti

1. Eseguire `php artisan migrate`: aggiunge preferenze e indici.
2. Confrontare la config pubblicata con quella nuova, senza sovrascrivere le personalizzazioni.
3. Per i nuovi audio scegliere un disk privato: una vecchia config pubblicata può ancora puntare al disk pubblico.
4. I vecchi URL pubblici restano pubblici finché l'applicazione non migra i file e i payload. Il package non sposta file esistenti automaticamente.
5. Il contatore indica adesso “da gestire”; la lettura è separata dalla gestione condivisa.
6. Adeguare eventuali messaggi HTML e chiamate GET al reset cache, che ora richiede POST e CSRF.
7. Riavviare i worker dopo il deployment. I job Laravel già accodati prima dell'aggiornamento non vengono riscritti.

Non è necessario cambiare le chiamate `roles(...)->users(...)->notification(...)->send()`.
Le nuove pagine inbox usano paginazione server; le integrazioni che personalizzavano
il vecchio datatable devono usare le view pubblicabili o l'API JSON.

## Sviluppo e verifiche

```bash
composer test
composer test:lint
composer validate --no-check-publish
```

Il workspace IlBronza dispone anche del runner condiviso descritto in `../AGENTS.md`.
Non usare `RefreshDatabase` con il runtime PHP WebAssembly di quel runner.
I test creano le tabelle Laravel di base e applicano le migrazioni reali del package.
La CI configura Composer con checkout dei package fratelli e una matrice
PHP 8.2/Laravel 11 e PHP 8.4/Laravel 12. Le dipendenze dell'applicazione vanno
comunque bloccate nel suo `composer.lock`.

Per provare il registratore, usare un browser reale su HTTPS/localhost: scelta del
microfono, annullamento, registrazione, limite dimensione, riproduzione e invio.
I test PHP verificano i percorsi server; non simulano un microfono fisico.

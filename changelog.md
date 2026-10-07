# Changelog

## Unreleased

- Isolamento per destinatario e ruolo su liste, dettaglio, modifica, cancellazione e audio.
- Builder corretto: destinatari normalizzati, deduplicati, gruppi database atomici e risultati coerenti con le code.
- Job che mantengono metadati e ID stabili; retry database senza duplicazioni.
- Contenuto delle notifiche figlie recuperato correttamente; gestione condivisa e lettura per ricevuta.
- Cancellazione della ricevuta principale senza perdere le copie degli altri destinatari.
- Inbox paginata, filtro non lette, creazione testuale, API JSON e azioni multiple.
- Header aggiornato, notifiche dei ruoli incluse, preferenza persistente e ripristino dall'inbox.
- Audio privato con route autorizzata, ricerca utenti, limiti e pulizia dei file.
- Registratore con scelta formato, gestione errori e rilascio del microfono.
- Trascrizione con stream remoti, timeout, limite dimensione e controllo degli host URL.
- Rollback corretto, indici e comando esplicito di conservazione.
- Dipendenze dichiarate, alias riparato, traduzioni EN/IT, documentazione e CI.

### Upgrade

Eseguire le migrazioni e confrontare la configurazione pubblicata. I messaggi sono
ora testo per default, il reset cache richiede POST e il contatore conta le notifiche
da gestire. Le vecchie configurazioni/file audio pubblici richiedono migrazione
nell'applicazione ospite. Consultare `readme.md` per gli altri dettagli di compatibilità.

## Version 1.0

- Initial release.

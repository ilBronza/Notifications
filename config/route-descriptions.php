<?php

// Comprende anche le azioni aggiunte da IlBronza\CRUD\ResourceRegistrar.
return [
    'notifications.header.show' => 'Mostra le notifiche nell’intestazione e riattiva la preferenza di visualizzazione.',
    'notifications.header.hide' => 'Nasconde le notifiche nell’intestazione per la sessione corrente.',
    'notifications.header.hidePermanently' => 'Salva la preferenza per nascondere le notifiche nell’intestazione.',
    'notifications.header.resetSessionCache' => 'Svuota la cache di sessione delle notifiche nell’intestazione.',
    'notifications.inbox' => 'Restituisce le notifiche della casella personale filtrate per stato.',
    'notifications.count' => 'Restituisce il numero delle notifiche non lette e da gestire.',
    'notifications.bulk' => 'Aggiorna in blocco lo stato di lettura o di gestione delle notifiche selezionate.',
    'notifications.archived' => 'Mostra le notifiche già gestite nella casella personale.',
    'notifications.audio' => 'Riproduce il file audio allegato alla notifica.',
    'notifications.index' => 'Mostra l’elenco delle notifiche.',
    'notifications.create' => 'Mostra il modulo per inviare una notifica testuale a un utente.',
    'notifications.createEmpty' => 'Crea una notifica con i valori predefiniti senza aprire il modulo.',
    'notifications.store' => 'Invia una notifica testuale all’utente selezionato.',
    'notifications.show' => 'Mostra i dettagli della notifica.',
    'notifications.edit' => 'Mostra il modulo di modifica della notifica.',
    'notifications.update' => 'Salva lo stato di lettura, lo stato di gestione o le note di gestione della notifica.',
    'notifications.destroy' => 'Elimina una notifica.',
    'notifications.list' => 'Richiede l’elenco delle notifiche eliminate; azione non implementata.',
    'notifications.voice-messages.index' => 'Mostra i destinatari per registrare e inviare un messaggio vocale.',
    'notifications.voice-messages.store' => 'Salva il file audio e invia una notifica vocale all’utente selezionato.',
];

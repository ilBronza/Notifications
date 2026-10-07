# Contributing

Le modifiche devono includere test del comportamento e note di compatibilità quando
cambiano API, schema o autorizzazioni. Prima di proporre una modifica eseguire:

```bash
composer validate --no-check-publish
composer test:lint
composer test
```

Nel workspace IlBronza seguire anche `../AGENTS.md` per il runner condiviso.
Le fixture costruiscono un database SQLite in memoria e applicano le migrazioni
reali: non sostituire i test delle migrazioni con una copia manuale dello schema.

Per invii e accessi includere casi con destinatari diversi, ruoli, code, retry e
operazioni annullate. Non inviare audio o messaggi reali nei test: usare storage,
HTTP e bus finti. Conservare le API legacy documentate oppure descrivere la
migrazione nel changelog e nel README.

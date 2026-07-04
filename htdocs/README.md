# Unfired Moto Club

Sito PHP/MySQL pronto per pubblicazione su hosting con form contatti, area admin ed eventi salvati su database.

## Struttura cartelle

- `public/`: file serviti dal web, pagine HTML, API, admin, upload e asset pubblici.
- `public/assets/`: immagini, CSS, JavaScript e documenti scaricabili.
- `src/`: codice PHP condiviso non pubblico.
- `config/`: configurazione database e admin.
- `migrations/`: aggiornamenti SQL.
- `tools/`: script da terminale.
- `tests/`: spazio per test automatici e smoke test futuri.

## File principali

- `public/index.html`: pagina pubblica del sito.
- `public/api/contact.php`: riceve il form contatti e salva il messaggio.
- `config/database.php`: credenziali del database.
- `config/admin.php`: credenziali dell'area admin.
- `public/admin/contacts.php`: elenco protetto dei messaggi ricevuti.
- `public/admin/events.php`: gestione eventi pubblicati sul sito.
- `public/admin/shop.php`: gestione prodotti shop, immagini caricate e richieste ricevute.
- `public/admin/login.php`: login del pannello admin.
- `database.sql`: script per creare database e tabella.
- `migrations/2026_06_29_admin_events.sql`: aggiornamento per database gia creati.
- `migrations/2026_06_30_shop.sql`: aggiunge prodotti shop e richieste prodotto.

## Installazione su XAMPP

1. Avvia Apache e MySQL.
2. Apri phpMyAdmin e crea un database, ad esempio `unfired_moto_club`.
3. Seleziona il database e importa `database.sql`.
4. Copia `.env.example` in `.env` e aggiorna credenziali database/admin se necessario.
5. Visita `http://localhost/`. La `.htaccess` principale inoltra automaticamente le richieste a `public/`.
6. Accedi al pannello da `http://localhost/admin/`.

Se il database era gia stato creato prima della gestione shop, importa anche:

- `migrations/2026_06_30_shop.sql`

In locale puoi accedere con l'utente admin predefinito solo per il primo test.
Cambia subito la password prima della pubblicazione online. Vedi `GUIDA_PASSWORD_ADMIN.md`.

## Messa online

1. Carica tutti i file sullo spazio hosting.
2. Crea un database MySQL dal pannello hosting.
3. Seleziona il database creato e importa `database.sql`.
4. Configura le variabili ambiente sull'hosting, oppure carica un file `.env` non pubblico con host, nome database, utente e password.
5. Verifica che `public/uploads/events` sia scrivibile dal server.
6. Verifica che `public/uploads/shop` sia scrivibile dal server.
7. Prima di accedere a `/admin/`, cambia la password admin o configura `ADMIN_PASSWORD_HASH` sull'hosting.

Per sicurezza, il login online rifiuta l'hash admin predefinito.

## Cambiare password admin

Guida completa: `GUIDA_PASSWORD_ADMIN.md`.

Su XAMPP aggiorna la password admin con:

```bash
C:\xampp\php\php.exe tools\admin-password.php
```

Lo script aggiorna `ADMIN_PASSWORD_HASH` in `.env` e azzera eventuali blocchi da troppi tentativi.

## Configurazione consigliata

Il sito legge prima le variabili ambiente e, in locale, anche il file `.env`.
Lascia `.env` fuori dal repository: contiene dati specifici della macchina o dell'hosting.

Valori principali:

- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `ADMIN_USERNAME`, `ADMIN_PASSWORD_HASH`
- `APP_ENV`, `APP_DEBUG`

## Controllo rapido

Da terminale, nella cartella del sito:

```bash
C:\xampp\php\php.exe tools\check.php
```

Lo script verifica versione PHP, estensioni richieste e cartelle upload scrivibili.

## Note sicurezza

- Le cartelle `config`, `src`, `migrations`, `tools` e `tests` sono bloccate via `.htaccess`.
- Le cartelle `public/uploads` rifiutano file PHP e disabilitano l'elenco directory.
- `.env`, log e file caricati dagli utenti sono esclusi da Git tramite `.gitignore`.
- I form pubblici validano i campi lato server e usano query preparate.

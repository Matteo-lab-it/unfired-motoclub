# Deploy con ZIP

Questo progetto puo essere aggiornato creando uno ZIP pulito da caricare sul server.

## Creare lo ZIP

Da PowerShell, nella cartella del progetto:

```powershell
.\tools\build-deploy-zip.ps1
```

Se Windows blocca l'esecuzione degli script PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build-deploy-zip.ps1
```

Il file viene creato in:

```text
dist/unfaired-deploy-YYYYMMDD-HHMMSS.zip
```

Lo ZIP non include:

- `.env`
- `.git`
- log
- pacchetti vecchi in `dist/`
- `vendor/` e `node_modules/`
- upload reali in `public/uploads/events` e `public/uploads/shop`
- `database.sql`
- `migrations/`

Per includere anche gli upload:

```powershell
.\tools\build-deploy-zip.ps1 -IncludeUploads
```

Oppure, se serve il bypass:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build-deploy-zip.ps1 -IncludeUploads
```

Per includere anche file database e migration, solo quando serve davvero:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build-deploy-zip.ps1 -IncludeDatabaseFiles
```

## Caricare sul server

Carica lo ZIP nella root pubblica del dominio e scompattalo li.

Lo ZIP contiene la struttura completa:

```text
.htaccess
public/
src/
config/
```

La `.htaccess` principale manda il traffico verso `public/`.

## File da non sovrascrivere

Non sovrascrivere il file `.env` del server. Lo script non lo mette nello ZIP apposta.

Il file `.env` deve gia esistere sul server con:

```text
DB_HOST
DB_DATABASE
DB_USERNAME
DB_PASSWORD
APP_ENCRYPTION_KEY
ADMIN_USERNAME
ADMIN_PASSWORD_HASH
```

Prima del trasferimento su Server Plan controlla che il `.env` del server non contenga piu host, utenti o password del provider precedente. In particolare aggiorna `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` con i valori indicati dal pannello Server Plan.

## Dopo il caricamento

Nel deploy normale non toccare il database.

Importa migration solo quando l'aggiornamento lo richiede esplicitamente. Per le iscrizioni, una sola volta, serve:

```text
migrations/2026_07_07_membership_requests.sql
```

Poi testa:

```text
https://tuodominio.it/api/membership-request.php
```

Non deve rispondere con una pagina WordPress 404. Deve rispondere JSON.

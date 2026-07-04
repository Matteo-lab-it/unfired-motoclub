# Guida password admin

Questa guida riguarda il sito Unfired Moto Club dentro C:/xampp/htdocs.

## Dove si trova

La configurazione admin si trova in config/admin.php.

Dentro trovi username e password_hash.

Username predefinito: admin

La password non e salvata in chiaro. Il valore password_hash e una versione cifrata: non si legge, si sostituisce.

## Password iniziale

La password iniziale non deve essere pubblicata in README o in altri file del sito.
Usala solo in locale per il primo test, poi sostituiscila subito.
Online il login rifiuta l'hash admin predefinito.

## Come cambiarla

1. Scegli una nuova password lunga e sicura.

2. Apri il terminale nella cartella C:/xampp/htdocs.

3. Su XAMPP puoi aggiornare la password con questo comando:

```bash
C:\xampp\php\php.exe tools\admin-password.php
```

4. Inserisci la nuova password quando richiesto.

Lo script aggiorna `ADMIN_PASSWORD_HASH` in `.env` e azzera eventuali blocchi da troppi tentativi.

5. Prova il login da http://localhost/admin/.

Usa username admin e la nuova password che hai scelto.

Se devi solo azzerare il blocco temporaneo dopo troppi tentativi:

```bash
C:\xampp\php\php.exe tools\admin-password.php --clear-locks
```

## Hosting

Se l hosting supporta variabili ambiente, puoi usare ADMIN_USERNAME e ADMIN_PASSWORD_HASH. In quel caso config/admin.php legge prima quelle variabili e usa il valore nel file solo come fallback.

## Attenzione

Non scrivere la password in chiaro nel file. Non pubblicarla nel README. Se la dimentichi, genera un nuovo hash e sostituisci password_hash.

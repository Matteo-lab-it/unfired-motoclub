<?php
declare(strict_types=1);

function admin_header(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($title) ?> - Unfaired Admin</title>
  <style>
    :root { --bg: #0f0f0f; --panel: #181818; --panel-2: #202020; --text: #f5f5f5; --muted: #b8b8b8; --line: rgba(255,255,255,.12); --accent: #ff5a1f; --accent-2: #ffb000; --danger: #ff6b6b; --ok: #7bd88f; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: var(--bg); color: var(--text); }
    a { color: inherit; text-decoration: none; }
    .shell { min-height: 100vh; display: grid; grid-template-columns: 260px 1fr; }
    .sidebar { padding: 24px; background: #090909; border-right: 1px solid var(--line); }
    .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; }
    .brand-mark { width: 42px; height: 42px; object-fit: cover; border-radius: 50%; background: #111; border: 1px solid rgba(255,255,255,.16); }
    .nav { display: grid; gap: 8px; }
    .nav a { padding: 12px 14px; border-radius: 10px; color: #ddd; border: 1px solid transparent; }
    .nav a:hover { background: rgba(255,255,255,.05); border-color: var(--line); color: var(--accent-2); }
    .main { padding: 32px; overflow-x: auto; }
    .topbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 28px; }
    h1 { margin: 0; font-size: clamp(1.8rem, 3vw, 2.6rem); }
    .muted { color: var(--muted); }
    .grid { display: grid; gap: 18px; }
    .stats { grid-template-columns: repeat(3, minmax(160px, 1fr)); margin-bottom: 22px; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 20px; }
    .stat strong { display: block; font-size: 2rem; color: var(--accent-2); }
    .actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .btn, button { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: 0 14px; border-radius: 10px; border: 1px solid var(--line); background: rgba(255,255,255,.05); color: var(--text); font-weight: 700; cursor: pointer; }
    .btn-primary { border: 0; color: #111; background: linear-gradient(135deg, var(--accent), var(--accent-2)); }
    .btn-danger { border-color: rgba(255,107,107,.45); color: #ffd5d5; }
    table { width: 100%; border-collapse: collapse; background: var(--panel); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
    th, td { padding: 14px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
    th { color: var(--accent-2); font-size: .82rem; text-transform: uppercase; }
    tr:last-child td { border-bottom: 0; }
    input, textarea, select { width: 100%; border: 1px solid var(--line); border-radius: 10px; background: #101010; color: #fff; padding: 12px; font: inherit; }
    textarea { min-height: 110px; resize: vertical; }
    label { display: grid; gap: 8px; color: var(--muted); font-size: .92rem; }
    .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .span-2 { grid-column: span 2; }
    .badge { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; border: 1px solid var(--line); color: var(--muted); font-size: .82rem; }
    .badge-ok { color: var(--ok); border-color: rgba(123,216,143,.45); }
    .badge-warn { color: var(--accent-2); border-color: rgba(255,176,0,.45); }
    .message { max-width: 360px; white-space: pre-wrap; }
    .notice { margin-bottom: 18px; padding: 12px 14px; border: 1px solid rgba(123,216,143,.35); border-radius: 10px; color: var(--ok); background: rgba(123,216,143,.08); }
    @media (max-width: 860px) {
      .shell { grid-template-columns: 1fr; }
      .sidebar { position: static; }
      .stats, .form-grid { grid-template-columns: 1fr; }
      .span-2 { grid-column: span 1; }
      table, thead, tbody, tr, th, td { display: block; }
      thead { display: none; }
      tr { border-bottom: 1px solid var(--line); }
      td::before { content: attr(data-label); display: block; color: var(--accent-2); font-size: .75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 4px; }
    }
  </style>
</head>
<body>
  <div class="shell">
    <aside class="sidebar">
      <a class="brand" href="/admin/">
        <img class="brand-mark" src="/assets/img/logo2.jpg" alt="Unfaired Moto Club">
        <span>Admin</span>
      </a>
      <nav class="nav">
        <a href="/admin/">Dashboard</a>
        <a href="/admin/memberships.php">Richieste iscrizione</a>
        <a href="/admin/contacts.php">Contatti</a>
        <a href="/admin/events.php">Eventi</a>
        <a href="/admin/shop.php">Shop</a>
        <a href="/" target="_blank">Vedi sito</a>
        <a href="/admin/logout.php">Esci</a>
      </nav>
    </aside>
    <main class="main">
      <div class="topbar">
        <div>
          <h1><?= h($title) ?></h1>
          <div class="muted">Unfaired Moto Club</div>
        </div>
      </div>
    <?php
}

function admin_footer(): void
{
    ?>
    </main>
  </div>
  <script src="/admin/assets/admin.js"></script>
</body>
</html>
    <?php
}

function admin_database_error(Throwable $exception): void
{
    error_log($exception->getMessage());
    http_response_code(503);
    admin_header('Database non disponibile');
    ?>
      <div class="card">
        <h2 style="margin-top:0;">MySQL non risponde</h2>
        <p class="muted">L'accesso admin e attivo, ma il database non accetta la connessione.</p>
        <div class="grid" style="margin-top:16px;">
          <div>Apri il pannello XAMPP e avvia <strong>MySQL</strong>.</div>
          <div>Controlla che in <strong>.env</strong> host, porta, nome database e utente siano corretti.</div>
          <div>Se il database non esiste ancora, crealo in phpMyAdmin e importa <strong>database.sql</strong>.</div>
        </div>
      </div>
    <?php
    admin_footer();
    exit;
}

<?php
declare(strict_types=1);

require __DIR__ . '/../../src/auth.php';

if (is_admin_logged_in()) {
    header('Location: /admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $rateLimit = admin_login_rate_limit_status($username);

    if ($rateLimit['locked']) {
        $minutes = max(1, (int) ceil($rateLimit['seconds_remaining'] / 60));
        $error = 'Troppi tentativi non riusciti. Riprova tra circa ' . $minutes . ' minuti.';
    } elseif (admin_default_password_is_blocked()) {
        $error = 'Password admin iniziale non valida online. Imposta una nuova password e riprova.';
    } elseif (attempt_admin_login($username, $password)) {
        header('Location: /admin/');
        exit;
    } else {
        $error = 'Credenziali non valide.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - Unfaired Moto Club</title>
  <style>
    body { min-height: 100vh; margin: 0; display: grid; place-items: center; font-family: Arial, Helvetica, sans-serif; background: radial-gradient(circle at 80% 10%, rgba(255,90,31,.18), transparent 25%), #0d0d0d; color: #f5f5f5; }
    form { width: min(92vw, 420px); display: grid; gap: 16px; padding: 28px; background: #181818; border: 1px solid rgba(255,255,255,.12); border-radius: 14px; }
    h1 { margin: 0 0 6px; }
    p { margin: 0; color: #b8b8b8; }
    label { display: grid; gap: 8px; color: #b8b8b8; }
    input { width: 100%; border: 1px solid rgba(255,255,255,.12); border-radius: 10px; background: #101010; color: #fff; padding: 13px; font: inherit; }
    button { min-height: 44px; border: 0; border-radius: 999px; background: linear-gradient(135deg, #ff5a1f, #ffb000); color: #111; font-weight: 900; cursor: pointer; }
    .error { padding: 11px; color: #ffd5d5; border: 1px solid rgba(255,107,107,.45); border-radius: 10px; background: rgba(255,107,107,.08); }
  </style>
</head>
<body>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div>
      <h1>Accesso admin</h1>
      <p>Gestione richieste, eventi e shop.</p>
    </div>
    <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    <label>Username
      <input type="text" name="username" autocomplete="username" required>
    </label>
    <label>Password
      <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button type="submit">Entra</button>
  </form>
</body>
</html>

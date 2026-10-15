<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/crypto.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/_layout.php';

require_admin();

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && $action === 'status') {
        $status = (string) ($_POST['status'] ?? 'new');
        $allowed = ['new', 'processing', 'completed', 'archived'];

        if (in_array($status, $allowed, true)) {
            $statement = db()->prepare('UPDATE membership_requests SET status = :status WHERE id = :id');
            $statement->execute(['status' => $status, 'id' => $id]);
        }
    }

    if ($id > 0 && $action === 'delete') {
        $statement = db()->prepare('DELETE FROM membership_requests WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    header('Location: /admin/memberships.php');
    exit;
}

$statusFilter = (string) ($_GET['status'] ?? '');
$allowedFilters = ['new', 'processing', 'completed', 'archived'];

if (in_array($statusFilter, $allowedFilters, true)) {
    $statement = db()->prepare('SELECT * FROM membership_requests WHERE status = :status ORDER BY created_at DESC');
    $statement->execute(['status' => $statusFilter]);
    $requests = $statement->fetchAll();
} else {
    $requests = db()
        ->query('SELECT * FROM membership_requests ORDER BY created_at DESC')
        ->fetchAll();
}

foreach ($requests as &$request) {
    $request['full_name'] = decrypt_value((string) $request['full_name']);
    $request['fiscal_code'] = decrypt_value((string) $request['fiscal_code']);
    $request['city'] = decrypt_value((string) $request['city']);
    $request['phone'] = decrypt_value((string) $request['phone']);
    $request['email'] = decrypt_value((string) $request['email']);
    $request['motorcycle_brand'] = decrypt_value($request['motorcycle_brand'] ?? null);
    $request['motorcycle_model'] = decrypt_value($request['motorcycle_model'] ?? null);
    $request['motorcycle_plate'] = decrypt_value($request['motorcycle_plate'] ?? null);
    $request['notes'] = decrypt_value($request['notes'] ?? null);
}
unset($request);
} catch (Throwable $exception) {
    admin_database_error($exception);
}

admin_header('Richieste iscrizione');
?>
<div class="actions" style="margin-bottom:18px;">
  <a class="btn" href="/admin/memberships.php">Tutte</a>
  <a class="btn" href="/admin/memberships.php?status=new">Nuove</a>
  <a class="btn" href="/admin/memberships.php?status=processing">In gestione</a>
  <a class="btn" href="/admin/memberships.php?status=completed">Completate</a>
  <a class="btn" href="/admin/memberships.php?status=archived">Archiviate</a>
</div>

<?php if (!$requests): ?>
  <div class="card">Non ci sono richieste di iscrizione.</div>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Data</th>
        <th>Richiedente</th>
        <th>Contatti</th>
        <th>Tessera</th>
        <th>Dati</th>
        <th>Moto</th>
        <th>Stato</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($requests as $request): ?>
        <tr>
          <td data-label="Data"><?= h($request['created_at']) ?></td>
          <td data-label="Richiedente">
            <strong><?= h($request['full_name']) ?></strong><br>
            <span class="muted"><?= h($request['birth_date']) ?></span>
          </td>
          <td data-label="Contatti">
            <a href="mailto:<?= h($request['email']) ?>"><?= h($request['email']) ?></a><br>
            <?= h($request['phone']) ?>
          </td>
          <td data-label="Tessera">
            <?= h($request['membership_type']) ?><br>
            <span class="muted">FMI gia attiva: <?= h($request['already_fmi']) ?></span>
          </td>
          <td data-label="Dati" class="message">
            Codice fiscale: <?= h($request['fiscal_code']) ?><br>
            Citta: <?= h($request['city']) ?><br>
            <?php if ($request['notes']): ?>
              Note: <?= nl2br(h($request['notes'])) ?>
            <?php endif; ?>
          </td>
          <td data-label="Moto" class="message">
            <?= h($request['motorcycle_brand'] ?: '-') ?><br>
            <?= h($request['motorcycle_model'] ?: '-') ?><br>
            Targa: <?= h($request['motorcycle_plate'] ?: '-') ?>
          </td>
          <td data-label="Stato">
            <form method="post" class="actions">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
              <select name="status" data-autosubmit="true">
                <option value="new" <?= $request['status'] === 'new' ? 'selected' : '' ?>>Nuova</option>
                <option value="processing" <?= $request['status'] === 'processing' ? 'selected' : '' ?>>In gestione</option>
                <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Completata</option>
                <option value="archived" <?= $request['status'] === 'archived' ? 'selected' : '' ?>>Archiviata</option>
              </select>
            </form>
          </td>
          <td data-label="Azioni">
            <form method="post" data-confirm="Eliminare questa richiesta di iscrizione?">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
              <button class="btn-danger" type="submit">Elimina</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_footer(); ?>

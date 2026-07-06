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
        $allowed = ['new', 'read', 'archived'];

        if (in_array($status, $allowed, true)) {
            $statement = db()->prepare('UPDATE contacts SET status = :status WHERE id = :id');
            $statement->execute(['status' => $status, 'id' => $id]);
        }
    }

    if ($id > 0 && $action === 'delete') {
        $statement = db()->prepare('DELETE FROM contacts WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    header('Location: /admin/contacts.php');
    exit;
}

$statusFilter = (string) ($_GET['status'] ?? '');
$allowedFilters = ['new', 'read', 'archived'];

if (in_array($statusFilter, $allowedFilters, true)) {
    $statement = db()->prepare('SELECT id, name, email, subject, message, status, created_at FROM contacts WHERE status = :status ORDER BY created_at DESC');
    $statement->execute(['status' => $statusFilter]);
    $contacts = $statement->fetchAll();
} else {
    $contacts = db()
        ->query('SELECT id, name, email, subject, message, status, created_at FROM contacts ORDER BY created_at DESC')
        ->fetchAll();
}

foreach ($contacts as &$contact) {
    $contact['name'] = decrypt_value((string) $contact['name']);
    $contact['email'] = decrypt_value((string) $contact['email']);
    $contact['subject'] = decrypt_value((string) $contact['subject']);
    $contact['message'] = decrypt_value((string) $contact['message']);
}
unset($contact);
} catch (Throwable $exception) {
    admin_database_error($exception);
}

admin_header('Richieste iscritti');
?>
<div class="actions" style="margin-bottom:18px;">
  <a class="btn" href="/admin/contacts.php">Tutte</a>
  <a class="btn" href="/admin/contacts.php?status=new">Nuove</a>
  <a class="btn" href="/admin/contacts.php?status=read">Lette</a>
  <a class="btn" href="/admin/contacts.php?status=archived">Archiviate</a>
</div>

<?php if (!$contacts): ?>
  <div class="card">Non ci sono richieste in questa sezione.</div>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Data</th>
        <th>Nome</th>
        <th>Email</th>
        <th>Oggetto</th>
        <th>Messaggio</th>
        <th>Stato</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($contacts as $contact): ?>
        <tr>
          <td data-label="Data"><?= h($contact['created_at']) ?></td>
          <td data-label="Nome"><?= h($contact['name']) ?></td>
          <td data-label="Email"><a href="mailto:<?= h($contact['email']) ?>"><?= h($contact['email']) ?></a></td>
          <td data-label="Oggetto"><?= h($contact['subject']) ?></td>
          <td data-label="Messaggio" class="message"><?= nl2br(h($contact['message'])) ?></td>
          <td data-label="Stato">
            <form method="post" class="actions">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
              <select name="status" data-autosubmit="true">
                <option value="new" <?= $contact['status'] === 'new' ? 'selected' : '' ?>>Nuova</option>
                <option value="read" <?= $contact['status'] === 'read' ? 'selected' : '' ?>>Letta</option>
                <option value="archived" <?= $contact['status'] === 'archived' ? 'selected' : '' ?>>Archiviata</option>
              </select>
            </form>
          </td>
          <td data-label="Azioni">
            <form method="post" data-confirm="Eliminare questa richiesta?">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
              <button class="btn-danger" type="submit">Elimina</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_footer(); ?>

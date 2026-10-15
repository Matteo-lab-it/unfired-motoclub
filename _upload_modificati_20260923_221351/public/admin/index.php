<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/crypto.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/_layout.php';

require_admin();

function dashboard_count(string $sql): int
{
    return (int) db()->query($sql)->fetchColumn();
}

function dashboard_rows(string $sql): array
{
    return db()->query($sql)->fetchAll();
}

try {
$stats = [
    'contacts' => dashboard_count('SELECT COUNT(*) FROM contacts'),
    'new_contacts' => dashboard_count("SELECT COUNT(*) FROM contacts WHERE status = 'new'"),
    'memberships' => dashboard_count('SELECT COUNT(*) FROM membership_requests'),
    'new_memberships' => dashboard_count("SELECT COUNT(*) FROM membership_requests WHERE status = 'new'"),
    'events' => dashboard_count('SELECT COUNT(*) FROM events'),
    'shop_requests' => dashboard_count('SELECT COUNT(*) FROM shop_requests'),
    'new_shop_requests' => dashboard_count("SELECT COUNT(*) FROM shop_requests WHERE status = 'new'"),
];

$latestMemberships = dashboard_rows('SELECT id, full_name, membership_type, status, created_at FROM membership_requests ORDER BY created_at DESC LIMIT 5');

$latestContacts = dashboard_rows('SELECT id, name, email, subject, status, created_at FROM contacts ORDER BY created_at DESC LIMIT 5');

$nextEvents = dashboard_rows('SELECT id, title, event_date, event_time, location, is_published FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC LIMIT 5');

$latestShopRequests = dashboard_rows('SELECT id, product_name, selected_size, quantity, customer_name, status, created_at FROM shop_requests ORDER BY created_at DESC LIMIT 5');

foreach ($latestMemberships as &$membership) {
    $membership['full_name'] = decrypt_value((string) $membership['full_name']);
}
unset($membership);

foreach ($latestContacts as &$contact) {
    $contact['name'] = decrypt_value((string) $contact['name']);
    $contact['email'] = decrypt_value((string) $contact['email']);
    $contact['subject'] = decrypt_value((string) $contact['subject']);
}
unset($contact);

foreach ($latestShopRequests as &$request) {
    $request['customer_name'] = decrypt_value((string) $request['customer_name']);
}
unset($request);
} catch (Throwable $exception) {
    admin_database_error($exception);
}

admin_header('Dashboard');
?>
<div class="grid stats">
  <div class="card stat"><span class="muted">Iscrizioni totali</span><strong><?= $stats['memberships'] ?></strong></div>
  <div class="card stat"><span class="muted">Iscrizioni nuove</span><strong><?= $stats['new_memberships'] ?></strong></div>
  <div class="card stat"><span class="muted">Contatti nuovi</span><strong><?= $stats['new_contacts'] ?></strong></div>
  <div class="card stat"><span class="muted">Eventi gestiti</span><strong><?= $stats['events'] ?></strong></div>
  <div class="card stat"><span class="muted">Richieste shop</span><strong><?= $stats['shop_requests'] ?></strong></div>
  <div class="card stat"><span class="muted">Shop nuove</span><strong><?= $stats['new_shop_requests'] ?></strong></div>
</div>

<div class="grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
  <section class="card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
      <h2 style="margin:0;">Ultime iscrizioni</h2>
      <a class="btn" href="/admin/memberships.php">Apri</a>
    </div>
    <?php if (!$latestMemberships): ?>
      <p class="muted">Nessuna richiesta di iscrizione ricevuta.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($latestMemberships as $membership): ?>
          <div>
            <strong><?= h($membership['full_name']) ?></strong>
            <div class="muted"><?= h($membership['membership_type']) ?> Â· <?= h($membership['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
      <h2 style="margin:0;">Ultimi contatti</h2>
      <a class="btn" href="/admin/contacts.php">Apri</a>
    </div>
    <?php if (!$latestContacts): ?>
      <p class="muted">Nessuna richiesta ricevuta.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($latestContacts as $contact): ?>
          <div>
            <strong><?= h($contact['name']) ?></strong>
            <div class="muted"><?= h($contact['subject']) ?> · <?= h($contact['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
      <h2 style="margin:0;">Prossimi eventi</h2>
      <a class="btn btn-primary" href="/admin/events.php">Gestisci</a>
    </div>
    <?php if (!$nextEvents): ?>
      <p class="muted">Nessun evento creato.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($nextEvents as $event): ?>
          <div>
            <strong><?= h($event['title']) ?></strong>
            <div class="muted"><?= h($event['event_date']) ?> <?= h(substr((string) $event['event_time'], 0, 5)) ?> · <?= h($event['location']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
      <h2 style="margin:0;">Richieste shop</h2>
      <a class="btn btn-primary" href="/admin/shop.php?tab=requests">Apri</a>
    </div>
    <?php if (!$latestShopRequests): ?>
      <p class="muted">Nessuna richiesta shop ricevuta.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($latestShopRequests as $request): ?>
          <div>
            <strong><?= h($request['customer_name']) ?></strong>
            <div class="muted"><?= h($request['product_name']) ?> · <?= h($request['selected_size']) ?> · q.ta <?= (int) $request['quantity'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php admin_footer(); ?>

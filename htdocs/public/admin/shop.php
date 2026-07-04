<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/crypto.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/_layout.php';

require_admin();

$notice = '';
$error = '';
$tab = (string) ($_GET['tab'] ?? 'products');

function public_shop_admin_image_path(string $path): string
{
    if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return $path;
    }

    return '/' . $path;
}

function delete_local_shop_image(?string $path): void
{
    if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return;
    }

    $fullPath = realpath(__DIR__ . '/../' . $path);
    $uploadRoot = realpath(__DIR__ . '/../uploads/shop');

    if ($fullPath && $uploadRoot && str_starts_with($fullPath, $uploadRoot) && is_file($fullPath)) {
        unlink($fullPath);
    }
}

function uploaded_shop_image_path(bool $required): ?string
{
    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Carica un immagine prodotto.');
        }

        return null;
    }

    if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload immagine non riuscito.');
    }

    if ((int) $_FILES['product_image']['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('L immagine deve pesare massimo 5 MB.');
    }

    $imageInfo = getimagesize((string) $_FILES['product_image']['tmp_name']);
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
        throw new RuntimeException('Carica un immagine JPG, PNG o WEBP.');
    }

    $uploadDir = __DIR__ . '/../uploads/shop';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('Non riesco a creare la cartella immagini shop.');
    }

    $filename = 'product-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$imageInfo[2]];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file((string) $_FILES['product_image']['tmp_name'], $destination)) {
        throw new RuntimeException('Non riesco a salvare l immagine caricata.');
    }

    return 'uploads/shop/' . $filename;
}

function normalize_sizes(string $sizes): string
{
    $parts = array_values(array_unique(array_filter(array_map(static function (string $size): string {
        return trim($size);
    }, explode(',', $sizes)))));

    return implode(', ', $parts);
}

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'save_product') {
        $currentImage = '';

        if ($id > 0) {
            $statement = db()->prepare('SELECT image_path FROM shop_products WHERE id = :id');
            $statement->execute(['id' => $id]);
            $currentImage = (string) ($statement->fetchColumn() ?: '');
        }

        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'sizes' => normalize_sizes((string) ($_POST['sizes'] ?? '')),
            'image_path' => $currentImage,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
        ];

        if ($data['name'] === '' || $data['description'] === '' || $data['sizes'] === '') {
            $error = 'Compila nome, descrizione e taglie.';
        } else {
            try {
                $uploadedImage = uploaded_shop_image_path($id === 0 && $currentImage === '');

                if ($uploadedImage !== null) {
                    delete_local_shop_image($currentImage);
                    $data['image_path'] = $uploadedImage;
                }

                if ($id > 0) {
                    $statement = db()->prepare(
                        'UPDATE shop_products
                         SET name = :name, description = :description, sizes = :sizes, image_path = :image_path,
                             sort_order = :sort_order, is_published = :is_published
                         WHERE id = :id'
                    );
                    $statement->execute($data + ['id' => $id]);
                    $notice = 'Prodotto aggiornato.';
                } else {
                    $statement = db()->prepare(
                        'INSERT INTO shop_products (name, description, sizes, image_path, sort_order, is_published)
                         VALUES (:name, :description, :sizes, :image_path, :sort_order, :is_published)'
                    );
                    $statement->execute($data);
                    $notice = 'Prodotto creato.';
                }
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        $tab = 'products';
    }

    if ($id > 0 && $action === 'delete_product') {
        $statement = db()->prepare('SELECT image_path FROM shop_products WHERE id = :id');
        $statement->execute(['id' => $id]);
        delete_local_shop_image((string) ($statement->fetchColumn() ?: ''));

        $statement = db()->prepare('DELETE FROM shop_products WHERE id = :id');
        $statement->execute(['id' => $id]);
        $notice = 'Prodotto eliminato.';
        $tab = 'products';
    }

    if ($id > 0 && $action === 'request_status') {
        $status = (string) ($_POST['status'] ?? 'new');
        $allowed = ['new', 'processing', 'completed', 'archived'];

        if (in_array($status, $allowed, true)) {
            $statement = db()->prepare('UPDATE shop_requests SET status = :status WHERE id = :id');
            $statement->execute(['status' => $status, 'id' => $id]);
        }

        $tab = 'requests';
    }

    if ($id > 0 && $action === 'delete_request') {
        $statement = db()->prepare('DELETE FROM shop_requests WHERE id = :id');
        $statement->execute(['id' => $id]);
        $notice = 'Richiesta eliminata.';
        $tab = 'requests';
    }
}

$editProduct = null;

if (isset($_GET['edit'])) {
    $statement = db()->prepare('SELECT * FROM shop_products WHERE id = :id');
    $statement->execute(['id' => (int) $_GET['edit']]);
    $editProduct = $statement->fetch() ?: null;
    $tab = 'products';
}

$products = db()
    ->query('SELECT * FROM shop_products ORDER BY sort_order ASC, id DESC')
    ->fetchAll();

$statusFilter = (string) ($_GET['status'] ?? '');
$allowedFilters = ['new', 'processing', 'completed', 'archived'];

if (in_array($statusFilter, $allowedFilters, true)) {
    $statement = db()->prepare('SELECT * FROM shop_requests WHERE status = :status ORDER BY created_at DESC');
    $statement->execute(['status' => $statusFilter]);
    $requests = $statement->fetchAll();
} else {
    $requests = db()
        ->query('SELECT * FROM shop_requests ORDER BY created_at DESC')
        ->fetchAll();
}

foreach ($requests as &$request) {
    $request['customer_name'] = decrypt_value((string) $request['customer_name']);
    $request['customer_email'] = decrypt_value((string) $request['customer_email']);
    $request['customer_phone'] = decrypt_value($request['customer_phone'] ?? null);
    $request['notes'] = decrypt_value($request['notes'] ?? null);
}
unset($request);

$formProduct = $editProduct ?: [
    'id' => 0,
    'name' => '',
    'description' => '',
    'sizes' => 'XS, S, M, L, XL, XXL',
    'image_path' => '',
    'sort_order' => 0,
    'is_published' => 1,
];
} catch (Throwable $exception) {
    admin_database_error($exception);
}

admin_header('Gestione shop');
?>
<?php if ($notice): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice" style="border-color:rgba(255,107,107,.45);color:#ffd5d5;background:rgba(255,107,107,.08);"><?= h($error) ?></div><?php endif; ?>

<div class="actions" style="margin-bottom:18px;">
  <a class="btn <?= $tab === 'products' ? 'btn-primary' : '' ?>" href="/admin/shop.php?tab=products">Prodotti</a>
  <a class="btn <?= $tab === 'requests' ? 'btn-primary' : '' ?>" href="/admin/shop.php?tab=requests">Richieste giunte</a>
</div>

<?php if ($tab === 'requests'): ?>
  <div class="actions" style="margin-bottom:18px;">
    <a class="btn" href="/admin/shop.php?tab=requests">Tutte</a>
    <a class="btn" href="/admin/shop.php?tab=requests&status=new">Nuove</a>
    <a class="btn" href="/admin/shop.php?tab=requests&status=processing">In gestione</a>
    <a class="btn" href="/admin/shop.php?tab=requests&status=completed">Completate</a>
    <a class="btn" href="/admin/shop.php?tab=requests&status=archived">Archiviate</a>
  </div>

  <?php if (!$requests): ?>
    <div class="card">Non ci sono richieste shop in questa sezione.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Data</th>
          <th>Prodotto</th>
          <th>Cliente</th>
          <th>Taglia</th>
          <th>Note</th>
          <th>Stato</th>
          <th>Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td data-label="Data"><?= h($request['created_at']) ?></td>
            <td data-label="Prodotto">
              <strong><?= h($request['product_name']) ?></strong>
              <div class="muted">Quantita: <?= (int) $request['quantity'] ?></div>
            </td>
            <td data-label="Cliente">
              <strong><?= h($request['customer_name']) ?></strong>
              <div><a href="mailto:<?= h($request['customer_email']) ?>"><?= h($request['customer_email']) ?></a></div>
              <?php if ($request['customer_phone']): ?><div class="muted"><?= h($request['customer_phone']) ?></div><?php endif; ?>
            </td>
            <td data-label="Taglia"><?= h($request['selected_size']) ?></td>
            <td data-label="Note" class="message"><?= nl2br(h($request['notes'] ?? '')) ?></td>
            <td data-label="Stato">
              <form method="post" class="actions">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="request_status">
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
              <form method="post" data-confirm="Eliminare questa richiesta shop?">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_request">
                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                <button class="btn-danger" type="submit">Elimina</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php else: ?>
  <section class="card" style="margin-bottom:22px;">
    <h2 style="margin-top:0;"><?= $editProduct ? 'Modifica prodotto' : 'Nuovo prodotto' ?></h2>
    <form method="post" class="form-grid" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_product">
      <input type="hidden" name="id" value="<?= (int) $formProduct['id'] ?>">

      <label>Nome prodotto
        <input type="text" name="name" value="<?= h($formProduct['name']) ?>" required>
      </label>

      <label>Ordine
        <input type="number" name="sort_order" value="<?= (int) $formProduct['sort_order'] ?>">
      </label>

      <label class="span-2">Taglie disponibili
        <input type="text" name="sizes" value="<?= h($formProduct['sizes']) ?>" placeholder="S, M, L, XL" required>
      </label>

      <label class="span-2">Immagine prodotto
        <input type="file" name="product_image" accept="image/jpeg,image/png,image/webp" <?= (int) $formProduct['id'] === 0 ? 'required' : '' ?>>
        <?php if ($formProduct['image_path']): ?>
          <span class="muted">Immagine attuale:</span>
          <img src="<?= h(public_shop_admin_image_path((string) $formProduct['image_path'])) ?>" alt="" style="width:220px;aspect-ratio:16/9;object-fit:cover;border-radius:10px;border:1px solid var(--line);">
        <?php endif; ?>
      </label>

      <label class="span-2">Descrizione
        <textarea name="description" required><?= h($formProduct['description']) ?></textarea>
      </label>

      <label style="display:flex; align-items:center; gap:10px;">
        <input style="width:auto;" type="checkbox" name="is_published" value="1" <?= (int) $formProduct['is_published'] === 1 ? 'checked' : '' ?>>
        Pubblicato nello shop
      </label>

      <div class="actions">
        <button class="btn-primary" type="submit"><?= $editProduct ? 'Salva modifiche' : 'Crea prodotto' ?></button>
        <?php if ($editProduct): ?><a class="btn" href="/admin/shop.php">Annulla</a><?php endif; ?>
      </div>
    </form>
  </section>

  <?php if (!$products): ?>
    <div class="card">Non ci sono ancora prodotti.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Prodotto</th>
          <th>Taglie</th>
          <th>Ordine</th>
          <th>Stato</th>
          <th>Azioni</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product): ?>
          <tr>
            <td data-label="Prodotto">
              <strong><?= h($product['name']) ?></strong>
              <div class="muted"><?= h($product['description']) ?></div>
              <img src="<?= h(public_shop_admin_image_path((string) $product['image_path'])) ?>" alt="" style="margin-top:10px;width:120px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;border:1px solid var(--line);">
            </td>
            <td data-label="Taglie"><?= h($product['sizes']) ?></td>
            <td data-label="Ordine"><?= (int) $product['sort_order'] ?></td>
            <td data-label="Stato">
              <span class="badge <?= (int) $product['is_published'] === 1 ? 'badge-ok' : 'badge-warn' ?>">
                <?= (int) $product['is_published'] === 1 ? 'Pubblicato' : 'Bozza' ?>
              </span>
            </td>
            <td data-label="Azioni">
              <div class="actions">
                <a class="btn" href="/admin/shop.php?edit=<?= (int) $product['id'] ?>">Modifica</a>
                <form method="post" data-confirm="Eliminare questo prodotto?">
                  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete_product">
                  <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                  <button class="btn-danger" type="submit">Elimina</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endif; ?>
<?php admin_footer(); ?>

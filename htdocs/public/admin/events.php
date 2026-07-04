<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/crypto.php';
require __DIR__ . '/../../src/auth.php';
require __DIR__ . '/_layout.php';

require_admin();

$notice = '';
$error = '';
$id = 0;

if (!empty($_SESSION['admin_events_notice'])) {
    $notice = (string) $_SESSION['admin_events_notice'];
    unset($_SESSION['admin_events_notice']);
}

if (!empty($_SESSION['admin_events_error'])) {
    $error = (string) $_SESSION['admin_events_error'];
    unset($_SESSION['admin_events_error']);
}

function redirect_admin_events(array $query = []): void
{
    $location = '/admin/events.php';
    if ($query) {
        $location .= '?' . http_build_query($query);
    }

    header('Location: ' . $location);
    exit;
}

function flash_notice(string $message): void
{
    $_SESSION['admin_events_notice'] = $message;
}

function flash_error(string $message): void
{
    $_SESSION['admin_events_error'] = $message;
}

function remember_event_form(array $data): void
{
    $_SESSION['admin_events_old_form'] = $data;
}

function consume_event_form(): ?array
{
    if (empty($_SESSION['admin_events_old_form']) || !is_array($_SESSION['admin_events_old_form'])) {
        return null;
    }

    $data = $_SESSION['admin_events_old_form'];
    unset($_SESSION['admin_events_old_form']);
    return $data;
}

function valid_date_value(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
}

function valid_time_value(string $value): bool
{
    return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value);
}

function posted_event_data(int $id, string $currentImage): array
{
    return [
        'id' => $id,
        'title' => trim((string) ($_POST['title'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'event_date' => (string) ($_POST['event_date'] ?? ''),
        'event_time' => (string) ($_POST['event_time'] ?? ''),
        'location' => trim((string) ($_POST['location'] ?? '')),
        'route_summary' => trim((string) ($_POST['route_summary'] ?? '')),
        'map_url' => trim((string) ($_POST['map_url'] ?? '')),
        'image_url' => $currentImage,
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
    ];
}

function validate_event_data(array $data): ?string
{
    if ($data['title'] === '' || $data['description'] === '' || $data['event_date'] === '' || $data['event_time'] === '' || $data['location'] === '') {
        return 'Compila titolo, descrizione, data, ora e luogo.';
    }

    if (strlen((string) $data['title']) > 160 || strlen((string) $data['location']) > 160 || strlen((string) $data['map_url']) > 500) {
        return 'Titolo, luogo o link mappa sono troppo lunghi.';
    }

    if (!valid_date_value((string) $data['event_date'])) {
        return 'Inserisci una data valida.';
    }

    if (!valid_time_value((string) $data['event_time'])) {
        return 'Inserisci un orario valido.';
    }

    if ($data['map_url'] !== '') {
        $scheme = strtolower((string) parse_url((string) $data['map_url'], PHP_URL_SCHEME));
        if (!filter_var($data['map_url'], FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            return 'Il link mappa deve essere un URL valido http o https.';
        }
    }

    return null;
}

function posted_route_stops(): array
{
    $titles = $_POST['route_stop_title'] ?? [];
    $descriptions = $_POST['route_stop_description'] ?? [];

    if (!is_array($titles)) {
        return [];
    }

    $stops = [];
    foreach ($titles as $index => $title) {
        $title = trim((string) $title);
        $description = trim((string) ($descriptions[$index] ?? ''));

        if ($title === '' && $description === '') {
            continue;
        }

        if ($title === '') {
            throw new RuntimeException('Ogni tappa con descrizione deve avere anche un titolo.');
        }

        if (strlen($title) > 120 || strlen($description) > 255) {
            throw new RuntimeException('Titolo o descrizione di una tappa sono troppo lunghi.');
        }

        $stops[] = [
            'title' => $title,
            'description' => $description,
        ];
    }

    return array_slice($stops, 0, 20);
}

function posted_route_stops_for_form(): array
{
    $titles = $_POST['route_stop_title'] ?? [];
    $descriptions = $_POST['route_stop_description'] ?? [];

    if (!is_array($titles)) {
        return [];
    }

    $stops = [];
    foreach ($titles as $index => $title) {
        $title = trim((string) $title);
        $description = trim((string) ($descriptions[$index] ?? ''));

        if ($title === '' && $description === '') {
            continue;
        }

        $stops[] = [
            'title' => $title,
            'description' => $description,
        ];
    }

    return array_slice($stops, 0, 20);
}

function save_route_stops(int $eventId, array $stops): void
{
    $delete = db()->prepare('DELETE FROM event_route_stops WHERE event_id = :event_id');
    $delete->execute(['event_id' => $eventId]);

    if (!$stops) {
        return;
    }

    $insert = db()->prepare(
        'INSERT INTO event_route_stops (event_id, title, description, sort_order)
         VALUES (:event_id, :title, :description, :sort_order)'
    );

    $sortOrder = 0;
    foreach ($stops as $stop) {
        $sortOrder += 10;
        $insert->execute([
            'event_id' => $eventId,
            'title' => $stop['title'],
            'description' => $stop['description'] !== '' ? $stop['description'] : null,
            'sort_order' => $sortOrder,
        ]);
    }
}

function public_image_path(string $path): string
{
    if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return $path;
    }

    return '/' . $path;
}

function delete_local_event_image(?string $path): void
{
    if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return;
    }

    $fullPath = realpath(__DIR__ . '/../' . $path);
    $uploadRoot = realpath(__DIR__ . '/../uploads/events');

    if ($fullPath && $uploadRoot && str_starts_with($fullPath, $uploadRoot) && is_file($fullPath)) {
        unlink($fullPath);
    }
}

function event_upload_extension(string $tmpName, int $size): string
{
    if ($size > 5 * 1024 * 1024) {
        throw new RuntimeException('Ogni immagine deve pesare massimo 5 MB.');
    }

    $imageInfo = getimagesize($tmpName);
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
        throw new RuntimeException('Carica immagini JPG, PNG o WEBP.');
    }

    return $allowedTypes[$imageInfo[2]];
}

function move_event_upload(string $tmpName, string $extension): string
{
    $uploadDir = __DIR__ . '/../uploads/events';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('Non riesco a creare la cartella immagini.');
    }

    $filename = 'event-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Non riesco a salvare l immagine caricata.');
    }

    return 'uploads/events/' . $filename;
}

function save_event_upload(string $tmpName, int $size): string
{
    return move_event_upload($tmpName, event_upload_extension($tmpName, $size));
}

function uploaded_event_image_path(): ?string
{
    if (!isset($_FILES['event_image']) || $_FILES['event_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['event_image']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload immagine non riuscito.');
    }

    return save_event_upload((string) $_FILES['event_image']['tmp_name'], (int) $_FILES['event_image']['size']);
}

function uploaded_event_gallery_paths(): array
{
    if (!isset($_FILES['gallery_images']) || !is_array($_FILES['gallery_images']['error'])) {
        return [];
    }

    $uploads = [];
    $paths = [];
    foreach ($_FILES['gallery_images']['error'] as $index => $errorCode) {
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload foto galleria non riuscito.');
        }

        $tmpName = (string) $_FILES['gallery_images']['tmp_name'][$index];
        $uploads[] = [
            'tmp_name' => $tmpName,
            'extension' => event_upload_extension($tmpName, (int) $_FILES['gallery_images']['size'][$index]),
        ];
    }

    try {
        foreach ($uploads as $upload) {
            $paths[] = move_event_upload($upload['tmp_name'], $upload['extension']);
        }
    } catch (RuntimeException $exception) {
        foreach ($paths as $path) {
            delete_local_event_image($path);
        }

        throw $exception;
    }

    return $paths;
}

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && $action === 'delete_photo') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $statement = db()->prepare('SELECT image_path FROM event_photos WHERE id = :id AND event_id = :event_id');
        $statement->execute(['id' => $photoId, 'event_id' => $id]);
        $photoPath = (string) ($statement->fetchColumn() ?: '');

        if ($photoPath !== '') {
            delete_local_event_image($photoPath);
            $deleteStatement = db()->prepare('DELETE FROM event_photos WHERE id = :id AND event_id = :event_id');
            $deleteStatement->execute(['id' => $photoId, 'event_id' => $id]);
            flash_notice('Foto rimossa dalla galleria.');
        }

        redirect_admin_events(['edit' => $id]);
    }

    if ($id > 0 && $action === 'delete_attendee') {
        $attendeeId = (int) ($_POST['attendee_id'] ?? 0);
        $deleteStatement = db()->prepare('DELETE FROM event_attendees WHERE id = :id AND event_id = :event_id');
        $deleteStatement->execute(['id' => $attendeeId, 'event_id' => $id]);
        flash_notice('Presenza rimossa.');
        $returnTo = (string) ($_POST['return_to'] ?? '');
        redirect_admin_events($returnTo === 'edit' ? ['edit' => $id] : []);
    }

    if ($action === 'save') {
        $currentImage = '';

        if ($id > 0) {
            $statement = db()->prepare('SELECT image_url FROM events WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $existingImage = $statement->fetchColumn();

            if ($existingImage === false) {
                flash_error('Evento non trovato.');
                redirect_admin_events();
            }

            $currentImage = (string) ($existingImage ?: '');
        }

        $data = posted_event_data($id, $currentImage);
        $validationError = validate_event_data($data);

        if ($validationError !== null) {
            $data['route_stops'] = posted_route_stops_for_form();
            remember_event_form($data);
            flash_error($validationError);
            redirect_admin_events($id > 0 ? ['edit' => $id] : []);
        }

        try {
            $routeStops = posted_route_stops();
            $uploadedImage = uploaded_event_image_path();
            $galleryPaths = uploaded_event_gallery_paths();
            $imagesToDeleteAfterCommit = [];

            if (isset($_POST['remove_event_image']) && $currentImage !== '') {
                $data['image_url'] = '';
                $imagesToDeleteAfterCommit[] = $currentImage;
            }

            if ($uploadedImage !== null) {
                if ($currentImage !== '') {
                    $imagesToDeleteAfterCommit[] = $currentImage;
                }
                $data['image_url'] = $uploadedImage;
            }

            db()->beginTransaction();

            if ($id > 0) {
                $statement = db()->prepare(
                    'UPDATE events
                     SET title = :title, description = :description, event_date = :event_date, event_time = :event_time,
                         location = :location, route_summary = :route_summary, map_url = :map_url,
                         image_url = :image_url, is_published = :is_published
                     WHERE id = :id'
                );
                $statement->execute($data);
                $eventId = $id;
                $message = 'Evento aggiornato.';
            } else {
                $statement = db()->prepare(
                    'INSERT INTO events (title, description, event_date, event_time, location, route_summary, map_url, image_url, is_published)
                     VALUES (:title, :description, :event_date, :event_time, :location, :route_summary, :map_url, :image_url, :is_published)'
                );
                $insertData = $data;
                unset($insertData['id']);
                $statement->execute($insertData);
                $eventId = (int) db()->lastInsertId();
                $message = 'Evento creato.';
            }

            save_route_stops($eventId, $routeStops);

            if ($galleryPaths) {
                $sortStatement = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM event_photos WHERE event_id = :event_id');
                $sortStatement->execute(['event_id' => $eventId]);
                $sortOrder = (int) $sortStatement->fetchColumn();
                $photoStatement = db()->prepare('INSERT INTO event_photos (event_id, image_path, sort_order) VALUES (:event_id, :image_path, :sort_order)');

                foreach ($galleryPaths as $path) {
                    $sortOrder += 10;
                    $photoStatement->execute([
                        'event_id' => $eventId,
                        'image_path' => $path,
                        'sort_order' => $sortOrder,
                    ]);
                }

                $message .= ' Foto galleria caricate.';
            }

            db()->commit();
            foreach (array_unique($imagesToDeleteAfterCommit) as $path) {
                delete_local_event_image($path);
            }
            flash_notice($message);
            redirect_admin_events(['edit' => $eventId]);
        } catch (RuntimeException $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            if (!empty($uploadedImage)) {
                delete_local_event_image($uploadedImage);
            }
            foreach ($galleryPaths ?? [] as $path) {
                delete_local_event_image($path);
            }
            $data['route_stops'] = posted_route_stops_for_form();
            remember_event_form($data);
            flash_error($exception->getMessage());
            redirect_admin_events($id > 0 ? ['edit' => $id] : []);
        }
    }

    if ($id > 0 && $action === 'delete') {
        $statement = db()->prepare('SELECT image_url FROM events WHERE id = :id');
        $statement->execute(['id' => $id]);
        $filesToDelete = [];
        $mainImage = (string) ($statement->fetchColumn() ?: '');
        if ($mainImage !== '') {
            $filesToDelete[] = $mainImage;
        }

        $photosStatement = db()->prepare('SELECT image_path FROM event_photos WHERE event_id = :event_id');
        $photosStatement->execute(['event_id' => $id]);
        foreach ($photosStatement->fetchAll() as $photo) {
            $filesToDelete[] = (string) $photo['image_path'];
        }

        db()->beginTransaction();
        $statement = db()->prepare('DELETE FROM events WHERE id = :id');
        $statement->execute(['id' => $id]);
        db()->commit();

        foreach (array_unique($filesToDelete) as $path) {
            delete_local_event_image($path);
        }

        flash_notice('Evento eliminato.');
        redirect_admin_events();
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'attendees') {
    $exportRows = db()
        ->query('SELECT e.title AS event_title, e.event_date, e.event_time, e.location,
                        a.name, a.motorcycle_model, a.created_at
                 FROM event_attendees a
                 INNER JOIN events e ON e.id = a.event_id
                 ORDER BY e.event_date ASC, e.event_time ASC, a.created_at DESC, a.id DESC')
        ->fetchAll();

    $filename = 'presenze-eventi-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('Impossibile generare il file export.');
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Evento', 'Data evento', 'Ora evento', 'Luogo', 'Nome', 'Modello moto', 'Registrata il'], ';');

    foreach ($exportRows as $row) {
        fputcsv($output, [
            $row['event_title'],
            $row['event_date'],
            substr((string) $row['event_time'], 0, 5),
            $row['location'],
            decrypt_value((string) $row['name']),
            decrypt_value((string) $row['motorcycle_model']),
            $row['created_at'],
        ], ';');
    }

    fclose($output);
    exit;
}

$editEvent = null;
$eventPhotos = [];
$eventAttendees = [];
$eventRouteStops = [];

if (isset($_GET['edit']) || $id > 0) {
    $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : $id;
    $statement = db()->prepare('SELECT * FROM events WHERE id = :id');
    $statement->execute(['id' => $editId]);
    $editEvent = $statement->fetch() ?: null;

    if ($editEvent) {
        $photosStatement = db()->prepare('SELECT * FROM event_photos WHERE event_id = :event_id ORDER BY sort_order ASC, id ASC');
        $photosStatement->execute(['event_id' => (int) $editEvent['id']]);
        $eventPhotos = $photosStatement->fetchAll();

        $routeStopsStatement = db()->prepare('SELECT * FROM event_route_stops WHERE event_id = :event_id ORDER BY sort_order ASC, id ASC');
        $routeStopsStatement->execute(['event_id' => (int) $editEvent['id']]);
        $eventRouteStops = $routeStopsStatement->fetchAll();

        $attendeesStatement = db()->prepare('SELECT * FROM event_attendees WHERE event_id = :event_id ORDER BY created_at DESC, id DESC');
        $attendeesStatement->execute(['event_id' => (int) $editEvent['id']]);
        $eventAttendees = $attendeesStatement->fetchAll();

        foreach ($eventAttendees as &$attendee) {
            $attendee['name'] = decrypt_value((string) $attendee['name']);
            $attendee['motorcycle_model'] = decrypt_value((string) $attendee['motorcycle_model']);
        }
        unset($attendee);
    }
}

$events = db()
    ->query('SELECT e.*,
                    (SELECT COUNT(*) FROM event_photos p WHERE p.event_id = e.id) AS photo_count,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS attendee_count
             FROM events e
             ORDER BY e.event_date ASC, e.event_time ASC')
    ->fetchAll();

$allAttendees = db()
    ->query('SELECT a.*, e.title AS event_title, e.event_date, e.event_time
             FROM event_attendees a
             INNER JOIN events e ON e.id = a.event_id
             ORDER BY e.event_date ASC, e.event_time ASC, a.created_at DESC, a.id DESC')
    ->fetchAll();

$attendeesByEvent = [];
foreach ($allAttendees as $attendee) {
    $attendee['name'] = decrypt_value((string) $attendee['name']);
    $attendee['motorcycle_model'] = decrypt_value((string) $attendee['motorcycle_model']);
    $attendeesByEvent[(int) $attendee['event_id']][] = $attendee;
}

$oldFormEvent = consume_event_form();
$oldRouteStops = [];
if ($oldFormEvent && isset($oldFormEvent['route_stops']) && is_array($oldFormEvent['route_stops'])) {
    $oldRouteStops = $oldFormEvent['route_stops'];
    unset($oldFormEvent['route_stops']);
}
$formEvent = $oldFormEvent ?: ($editEvent ?: [
    'id' => 0,
    'title' => '',
    'description' => '',
    'event_date' => '',
    'event_time' => '',
    'location' => '',
    'route_summary' => '',
    'map_url' => '',
    'image_url' => '',
    'is_published' => 1,
]);
$formRouteStops = $oldRouteStops ?: $eventRouteStops;
} catch (Throwable $exception) {
    try {
        $connection = db();
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
    } catch (Throwable) {
    }
    admin_database_error($exception);
}

admin_header('Gestione eventi');
?>
<?php if ($notice): ?><div class="notice"><?= h($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice" style="border-color:rgba(255,107,107,.45);color:#ffd5d5;background:rgba(255,107,107,.08);"><?= h($error) ?></div><?php endif; ?>

<section class="card" style="margin-bottom:22px;">
  <div class="actions" style="justify-content:space-between;margin-bottom:14px;">
    <h2 style="margin:0;">Presenze registrate</h2>
    <div class="actions">
      <span class="badge badge-ok"><?= count($allAttendees) ?> totali</span>
      <a class="btn btn-primary" href="/admin/events.php?export=attendees">Scarica Excel</a>
    </div>
  </div>
  <?php if (!$allAttendees): ?>
    <p class="muted">Nessuna presenza registrata dagli eventi pubblicati.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($events as $event): ?>
        <?php $eventAttendeeList = $attendeesByEvent[(int) $event['id']] ?? []; ?>
        <?php if (!$eventAttendeeList) continue; ?>
        <div style="border:1px solid var(--line);border-radius:10px;background:#101010;padding:14px;">
          <div class="actions" style="justify-content:space-between;margin-bottom:10px;">
            <div>
              <strong><?= h($event['title']) ?></strong>
              <div class="muted"><?= h($event['event_date']) ?> <?= h(substr((string) $event['event_time'], 0, 5)) ?></div>
            </div>
            <span class="badge badge-ok"><?= count($eventAttendeeList) ?> presenti</span>
          </div>
          <table>
            <thead>
              <tr>
                <th>Nome</th>
                <th>Moto</th>
                <th>Registrata il</th>
                <th>Azioni</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($eventAttendeeList as $attendee): ?>
                <tr>
                  <td data-label="Nome"><strong><?= h($attendee['name']) ?></strong></td>
                  <td data-label="Moto"><?= h($attendee['motorcycle_model']) ?></td>
                  <td data-label="Registrata il"><?= h($attendee['created_at']) ?></td>
                  <td data-label="Azioni">
                    <form method="post" data-confirm="Rimuovere questa presenza?">
                      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_attendee">
                      <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                      <input type="hidden" name="attendee_id" value="<?= (int) $attendee['id'] ?>">
                      <input type="hidden" name="return_to" value="list">
                      <button class="btn-danger" type="submit">Rimuovi</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="card" style="margin-bottom:22px;">
  <h2 style="margin-top:0;"><?= $editEvent ? 'Modifica evento' : 'Nuovo evento' ?></h2>
  <form method="post" class="form-grid" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) $formEvent['id'] ?>">

    <label>Titolo
      <input type="text" name="title" value="<?= h($formEvent['title']) ?>" required>
    </label>

    <label>Luogo
      <input type="text" name="location" value="<?= h($formEvent['location']) ?>" required>
    </label>

    <label>Data
      <input type="date" name="event_date" value="<?= h($formEvent['event_date']) ?>" required>
    </label>

    <label>Ora
      <input type="time" name="event_time" value="<?= h(substr((string) $formEvent['event_time'], 0, 5)) ?>" required>
    </label>

    <label class="span-2">Link mappa reale
      <input type="url" name="map_url" value="<?= h($formEvent['map_url']) ?>" placeholder="https://www.google.com/maps/...">
      <span class="muted">Il link viene aperto in una nuova scheda dalla pagina evento.</span>
    </label>

    <div class="span-2" style="display:grid;gap:12px;">
      <div>
        <strong style="color:#fff;">Tappe Roadbook</strong>
        <div class="muted">Compila titolo e descrizione delle tappe. Aggiungi nuove righe solo quando servono.</div>
      </div>
      <?php
        $routeStopRows = array_values($formRouteStops);
        $rowCount = max(1, count($routeStopRows));
      ?>
      <div id="routeStopsList" style="display:grid;gap:10px;">
        <?php for ($index = 0; $index < $rowCount; $index++): ?>
          <?php $stop = $routeStopRows[$index] ?? ['title' => '', 'description' => '']; ?>
          <div class="route-stop-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;align-items:start;">
            <label>Localita <?= $index + 1 ?>
              <input type="text" name="route_stop_title[]" value="<?= h((string) ($stop['title'] ?? '')) ?>" maxlength="120" placeholder="Es. Torino">
            </label>
          <label>Link Maps nascosto
            <input type="text" name="route_stop_description[]" value="<?= h((string) ($stop['description'] ?? '')) ?>" maxlength="255" placeholder="Incolla link Google Maps / Apple Mappe">
          </label>
          </div>
        <?php endfor; ?>
      </div>
      <button class="btn" type="button" id="addRouteStopButton">Aggiungi tappa</button>
    </div>

    <label class="span-2">Immagine evento
      <input type="file" name="event_image" accept="image/jpeg,image/png,image/webp">
      <?php if ($formEvent['image_url']): ?>
        <span class="muted">Immagine attuale:</span>
        <img src="<?= h(public_image_path((string) $formEvent['image_url'])) ?>" alt="" style="width:220px;aspect-ratio:16/9;object-fit:cover;border-radius:10px;border:1px solid var(--line);">
        <span style="display:flex;align-items:center;gap:10px;color:#ddd;">
          <input style="width:auto;" type="checkbox" name="remove_event_image" value="1">
          Rimuovi immagine attuale
        </span>
      <?php endif; ?>
    </label>

    <label class="span-2">Foto galleria post-uscita
      <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
      <span class="muted">Puoi caricare piu foto dopo l uscita. Ogni file deve pesare massimo 5 MB.</span>
    </label>


    <label class="span-2">Descrizione
      <textarea name="description" required><?= h($formEvent['description']) ?></textarea>
    </label>

    <label style="display:flex; align-items:center; gap:10px;">
      <input style="width:auto;" type="checkbox" name="is_published" value="1" <?= (int) $formEvent['is_published'] === 1 ? 'checked' : '' ?>>
      Pubblicato sul sito
    </label>

    <div class="actions">
      <button class="btn-primary" type="submit"><?= $editEvent ? 'Salva modifiche' : 'Crea evento' ?></button>
      <?php if ($editEvent): ?><a class="btn" href="/admin/events.php">Annulla</a><?php endif; ?>
    </div>
  </form>
</section>
<?php if ($eventPhotos): ?>
  <section class="card" style="margin-bottom:22px;">
    <h2 style="margin-top:0;">Foto galleria</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;">
      <?php foreach ($eventPhotos as $photo): ?>
        <div style="border:1px solid var(--line);border-radius:10px;padding:10px;background:#101010;">
          <img src="<?= h(public_image_path((string) $photo['image_path'])) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;margin-bottom:8px;">
          <form method="post" data-confirm="Rimuovere questa foto?">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_photo">
            <input type="hidden" name="id" value="<?= (int) $formEvent['id'] ?>">
            <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
            <button class="btn-danger" type="submit" style="width:100%;">Rimuovi</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
<?php if ($editEvent): ?>
  <section class="card" style="margin-bottom:22px;">
    <div class="actions" style="justify-content:space-between;margin-bottom:14px;">
      <h2 style="margin:0;">Presenze evento</h2>
      <span class="badge badge-ok"><?= count($eventAttendees) ?> presenti</span>
    </div>
    <?php if (!$eventAttendees): ?>
      <p class="muted">Nessuna presenza registrata per questo evento.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>Moto</th>
            <th>Registrata il</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($eventAttendees as $attendee): ?>
            <tr>
              <td data-label="Nome"><strong><?= h($attendee['name']) ?></strong></td>
              <td data-label="Moto"><?= h($attendee['motorcycle_model']) ?></td>
              <td data-label="Registrata il"><?= h($attendee['created_at']) ?></td>
              <td data-label="Azioni">
                <form method="post" data-confirm="Rimuovere questa presenza?">
                  <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete_attendee">
                  <input type="hidden" name="id" value="<?= (int) $formEvent['id'] ?>">
                  <input type="hidden" name="attendee_id" value="<?= (int) $attendee['id'] ?>">
                  <input type="hidden" name="return_to" value="edit">
                  <button class="btn-danger" type="submit">Rimuovi</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php if (!$events): ?>
  <div class="card">Non ci sono ancora eventi.</div>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Data</th>
        <th>Titolo</th>
        <th>Luogo</th>
        <th>Galleria</th>
        <th>Presenze</th>
        <th>Stato</th>
        <th>Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $event): ?>
        <tr>
          <td data-label="Data"><?= h($event['event_date']) ?> <?= h(substr((string) $event['event_time'], 0, 5)) ?></td>
          <td data-label="Titolo">
            <strong><?= h($event['title']) ?></strong>
            <div class="muted"><?= h($event['description']) ?></div>
            <?php if ($event['route_summary']): ?>
              <div class="muted" style="margin-top:6px;">Percorso: <?= h($event['route_summary']) ?></div>
            <?php endif; ?>
            <?php if ($event['image_url']): ?>
              <img src="<?= h(public_image_path((string) $event['image_url'])) ?>" alt="" style="margin-top:10px;width:120px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;border:1px solid var(--line);">
            <?php endif; ?>
          </td>
          <td data-label="Luogo"><?= h($event['location']) ?></td>
          <td data-label="Galleria"><?= (int) $event['photo_count'] ?> foto</td>
          <td data-label="Presenze"><?= (int) $event['attendee_count'] ?> presenti</td>
          <td data-label="Stato">
            <span class="badge <?= (int) $event['is_published'] === 1 ? 'badge-ok' : 'badge-warn' ?>">
              <?= (int) $event['is_published'] === 1 ? 'Pubblicato' : 'Bozza' ?>
            </span>
          </td>
          <td data-label="Azioni">
            <div class="actions">
              <a class="btn" href="/event.html?id=<?= (int) $event['id'] ?>" target="_blank">Pagina</a>
              <a class="btn" href="/admin/events.php?edit=<?= (int) $event['id'] ?>">Modifica</a>
              <form method="post" data-confirm="Eliminare questo evento?">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                <button class="btn-danger" type="submit">Elimina</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<script src="/admin/assets/events.js"></script>
<?php admin_footer(); ?>

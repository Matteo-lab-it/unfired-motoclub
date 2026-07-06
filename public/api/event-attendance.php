<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/crypto.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito.',
    ]);
    exit;
}

$eventId = (int) ($_POST['event_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$motorcycleModel = trim((string) ($_POST['motorcycle_model'] ?? ''));
$website = trim((string) ($_POST['website'] ?? ''));

if ($website !== '') {
    echo json_encode([
        'success' => true,
        'message' => 'Presenza registrata. Ci vediamo all evento!',
        'registered' => false,
    ]);
    exit;
}

if ($eventId <= 0 || $name === '' || $motorcycleModel === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Compila nome e modello moto.',
    ]);
    exit;
}

if (strlen($name) > 120 || strlen($motorcycleModel) > 160) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Uno dei campi e troppo lungo.',
    ]);
    exit;
}

try {
    $eventStatement = db()->prepare('SELECT id FROM events WHERE id = :id AND is_published = 1 LIMIT 1');
    $eventStatement->execute(['id' => $eventId]);

    if (!$eventStatement->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Evento non disponibile.',
        ]);
        exit;
    }

    $duplicateStatement = db()->prepare('SELECT name, motorcycle_model FROM event_attendees WHERE event_id = :event_id');
    $duplicateStatement->execute(['event_id' => $eventId]);

    foreach ($duplicateStatement->fetchAll() as $attendee) {
        if (
            strtolower(decrypt_value((string) $attendee['name'])) === strtolower($name)
            && strtolower(decrypt_value((string) $attendee['motorcycle_model'])) === strtolower($motorcycleModel)
        ) {
            echo json_encode([
                'success' => true,
                'message' => 'Presenza gia registrata per questo evento.',
                'registered' => false,
            ]);
            exit;
        }
    }

    $insert = db()->prepare(
        'INSERT INTO event_attendees (event_id, name, motorcycle_model, ip_address, user_agent)
         VALUES (:event_id, :name, :motorcycle_model, :ip_address, :user_agent)'
    );

    $insert->execute([
        'event_id' => $eventId,
        'name' => encrypt_value($name),
        'motorcycle_model' => encrypt_value($motorcycleModel),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Presenza registrata. Ci vediamo all evento!',
        'registered' => true,
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore temporaneo. Riprova tra qualche minuto.',
    ]);
}

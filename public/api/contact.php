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

$name = trim((string) ($_POST['nome'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['oggetto'] ?? ''));
$body = trim((string) ($_POST['messaggio'] ?? ''));
$website = trim((string) ($_POST['website'] ?? ''));

if ($website !== '') {
    echo json_encode([
        'success' => true,
        'message' => 'Messaggio inviato correttamente. Ti ricontatteremo presto.',
    ]);
    exit;
}

if ($name === '' || $email === '' || $subject === '' || $body === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Compila tutti i campi richiesti.',
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Inserisci un indirizzo email valido.',
    ]);
    exit;
}

if (strlen($name) > 120 || strlen($email) > 190 || strlen($subject) > 190) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Uno dei campi e troppo lungo.',
    ]);
    exit;
}

try {
    $statement = db()->prepare(
        'INSERT INTO contacts (name, email, subject, message, ip_address, user_agent)
         VALUES (:name, :email, :subject, :message, :ip_address, :user_agent)'
    );

    $statement->execute([
        'name' => encrypt_value($name),
        'email' => encrypt_value($email),
        'subject' => encrypt_value($subject),
        'message' => encrypt_value($body),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Messaggio inviato correttamente. Ti ricontatteremo presto.',
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore temporaneo. Riprova tra qualche minuto.',
    ]);
}

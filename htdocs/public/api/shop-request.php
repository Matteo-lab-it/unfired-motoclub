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

$productId = (int) ($_POST['product_id'] ?? 0);
$size = trim((string) ($_POST['size'] ?? ''));
$quantity = max(1, min(20, (int) ($_POST['quantity'] ?? 1)));
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));
$website = trim((string) ($_POST['website'] ?? ''));

if ($website !== '') {
    echo json_encode([
        'success' => true,
        'message' => 'Richiesta inviata correttamente. Ti ricontatteremo presto.',
    ]);
    exit;
}

if ($productId <= 0 || $size === '' || $name === '' || $email === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Compila prodotto, taglia, nome ed email.',
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

if (strlen($name) > 120 || strlen($email) > 190 || strlen($phone) > 40 || strlen($size) > 40) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Uno dei campi e troppo lungo.',
    ]);
    exit;
}

try {
    $statement = db()->prepare('SELECT id, name, sizes FROM shop_products WHERE id = :id AND is_published = 1');
    $statement->execute(['id' => $productId]);
    $product = $statement->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Prodotto non disponibile.',
        ]);
        exit;
    }

    $allowedSizes = array_values(array_filter(array_map('trim', explode(',', (string) $product['sizes']))));

    if (!in_array($size, $allowedSizes, true)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Seleziona una taglia disponibile.',
        ]);
        exit;
    }

    $insert = db()->prepare(
        'INSERT INTO shop_requests
         (product_id, product_name, selected_size, quantity, customer_name, customer_email, customer_phone, notes, ip_address, user_agent)
         VALUES
         (:product_id, :product_name, :selected_size, :quantity, :customer_name, :customer_email, :customer_phone, :notes, :ip_address, :user_agent)'
    );

    $insert->execute([
        'product_id' => (int) $product['id'],
        'product_name' => $product['name'],
        'selected_size' => $size,
        'quantity' => $quantity,
        'customer_name' => encrypt_value($name),
        'customer_email' => encrypt_value($email),
        'customer_phone' => $phone !== '' ? encrypt_value($phone) : null,
        'notes' => $notes !== '' ? encrypt_value($notes) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Richiesta inviata correttamente. Ti ricontatteremo presto.',
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore temporaneo. Riprova tra qualche minuto.',
    ]);
}

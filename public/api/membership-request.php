<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/crypto.php';
require __DIR__ . '/../../src/rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito.',
    ]);
    exit;
}

public_api_rate_limit('membership-request', 5, 600);

$membershipType = trim((string) ($_POST['tessera'] ?? ''));
$fullName = trim((string) ($_POST['nome'] ?? ''));
$birthDate = trim((string) ($_POST['nascita'] ?? ''));
$fiscalCode = strtoupper(trim((string) ($_POST['codicefiscale'] ?? '')));
$city = trim((string) ($_POST['citta'] ?? ''));
$phone = trim((string) ($_POST['telefono'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$motorcycleBrand = trim((string) ($_POST['marca_moto'] ?? ''));
$motorcycleModel = trim((string) ($_POST['modello_moto'] ?? ''));
$motorcyclePlate = strtoupper(trim((string) ($_POST['targa_moto'] ?? '')));
$alreadyFmi = trim((string) ($_POST['fmi'] ?? 'No'));
$notes = trim((string) ($_POST['note'] ?? ''));
$privacy = (string) ($_POST['privacy'] ?? '');
$website = trim((string) ($_POST['website'] ?? ''));

if ($website !== '') {
    echo json_encode([
        'success' => true,
        'message' => 'Richiesta inviata correttamente. Ti ricontatteremo presto.',
    ]);
    exit;
}

$allowedMembershipTypes = ['Member FMI', 'Sport FMI', 'Mini Sport FMI'];
$allowedFmiValues = ['Si', 'No'];

if (
    !in_array($membershipType, $allowedMembershipTypes, true)
    || $fullName === ''
    || $birthDate === ''
    || $fiscalCode === ''
    || $city === ''
    || $phone === ''
    || $email === ''
    || $privacy !== '1'
) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Compila tutti i campi richiesti e accetta la Privacy Policy.',
    ]);
    exit;
}

$birthDateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
if (!$birthDateObject || $birthDateObject->format('Y-m-d') !== $birthDate) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Inserisci una data di nascita valida.',
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

if (!in_array($alreadyFmi, $allowedFmiValues, true)) {
    $alreadyFmi = 'No';
}

if (
    strlen($fullName) > 120
    || strlen($email) > 190
    || strlen($fiscalCode) > 32
    || strlen($city) > 120
    || strlen($phone) > 60
    || strlen($motorcycleBrand) > 120
    || strlen($motorcycleModel) > 120
    || strlen($motorcyclePlate) > 40
    || strlen($notes) > 2000
) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Uno dei campi e troppo lungo.',
    ]);
    exit;
}

try {
    $statement = db()->prepare(
        'INSERT INTO membership_requests
         (membership_type, full_name, birth_date, fiscal_code, city, phone, email,
          motorcycle_brand, motorcycle_model, motorcycle_plate, already_fmi, notes, ip_address, user_agent)
         VALUES
         (:membership_type, :full_name, :birth_date, :fiscal_code, :city, :phone, :email,
          :motorcycle_brand, :motorcycle_model, :motorcycle_plate, :already_fmi, :notes, :ip_address, :user_agent)'
    );

    $statement->execute([
        'membership_type' => $membershipType,
        'full_name' => encrypt_value($fullName),
        'birth_date' => $birthDate,
        'fiscal_code' => encrypt_value($fiscalCode),
        'city' => encrypt_value($city),
        'phone' => encrypt_value($phone),
        'email' => encrypt_value($email),
        'motorcycle_brand' => $motorcycleBrand !== '' ? encrypt_value($motorcycleBrand) : null,
        'motorcycle_model' => $motorcycleModel !== '' ? encrypt_value($motorcycleModel) : null,
        'motorcycle_plate' => $motorcyclePlate !== '' ? encrypt_value($motorcyclePlate) : null,
        'already_fmi' => $alreadyFmi,
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

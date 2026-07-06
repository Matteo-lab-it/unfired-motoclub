<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/crypto.php';

function is_encrypted_value(?string $value): bool
{
    return is_string($value) && str_starts_with($value, ENCRYPTED_VALUE_PREFIX);
}

function encrypt_contacts(): int
{
    $count = 0;
    $rows = db()->query('SELECT id, name, email, subject, message FROM contacts')->fetchAll();
    $update = db()->prepare(
        'UPDATE contacts
         SET name = :name, email = :email, subject = :subject, message = :message
         WHERE id = :id'
    );

    foreach ($rows as $row) {
        $changed = false;
        $data = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'subject' => (string) $row['subject'],
            'message' => (string) $row['message'],
        ];

        foreach (['name', 'email', 'subject', 'message'] as $field) {
            if (!is_encrypted_value($data[$field])) {
                $data[$field] = encrypt_value($data[$field]);
                $changed = true;
            }
        }

        if ($changed) {
            $update->execute($data);
            $count++;
        }
    }

    return $count;
}

function encrypt_shop_requests(): int
{
    $count = 0;
    $rows = db()->query('SELECT id, customer_name, customer_email, customer_phone, notes FROM shop_requests')->fetchAll();
    $update = db()->prepare(
        'UPDATE shop_requests
         SET customer_name = :customer_name, customer_email = :customer_email,
             customer_phone = :customer_phone, notes = :notes
         WHERE id = :id'
    );

    foreach ($rows as $row) {
        $changed = false;
        $data = [
            'id' => (int) $row['id'],
            'customer_name' => (string) $row['customer_name'],
            'customer_email' => (string) $row['customer_email'],
            'customer_phone' => $row['customer_phone'],
            'notes' => $row['notes'],
        ];

        foreach (['customer_name', 'customer_email', 'customer_phone', 'notes'] as $field) {
            if ($data[$field] !== null && !is_encrypted_value((string) $data[$field])) {
                $data[$field] = encrypt_value((string) $data[$field]);
                $changed = true;
            }
        }

        if ($changed) {
            $update->execute($data);
            $count++;
        }
    }

    return $count;
}

function encrypt_event_attendees(): int
{
    $count = 0;
    $rows = db()->query('SELECT id, name, motorcycle_model FROM event_attendees')->fetchAll();
    $update = db()->prepare(
        'UPDATE event_attendees
         SET name = :name, motorcycle_model = :motorcycle_model
         WHERE id = :id'
    );

    foreach ($rows as $row) {
        $changed = false;
        $data = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'motorcycle_model' => (string) $row['motorcycle_model'],
        ];

        foreach (['name', 'motorcycle_model'] as $field) {
            if (!is_encrypted_value($data[$field])) {
                $data[$field] = encrypt_value($data[$field]);
                $changed = true;
            }
        }

        if ($changed) {
            $update->execute($data);
            $count++;
        }
    }

    return $count;
}

try {
    db()->beginTransaction();
    $contacts = encrypt_contacts();
    $shopRequests = encrypt_shop_requests();
    $eventAttendees = encrypt_event_attendees();
    db()->commit();

    echo "Cifratura completata.\n";
    echo "Contatti aggiornati: {$contacts}\n";
    echo "Richieste shop aggiornate: {$shopRequests}\n";
    echo "Presenze eventi aggiornate: {$eventAttendees}\n";
} catch (Throwable $exception) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

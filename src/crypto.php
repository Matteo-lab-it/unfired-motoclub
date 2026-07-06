<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

const ENCRYPTED_VALUE_PREFIX = 'enc:v1:';

function app_encryption_key(): string
{
    static $key = null;

    if (is_string($key)) {
        return $key;
    }

    $configuredKey = env_value('APP_ENCRYPTION_KEY', '');
    if (str_starts_with($configuredKey, 'base64:')) {
        $decoded = base64_decode(substr($configuredKey, 7), true);
        if ($decoded !== false && strlen($decoded) === 32) {
            $key = $decoded;
            return $key;
        }
    }

    if (strlen($configuredKey) === 32) {
        $key = $configuredKey;
        return $key;
    }

    throw new RuntimeException('APP_ENCRYPTION_KEY non configurata correttamente.');
}

function encrypt_value(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($value, 'aes-256-gcm', app_encryption_key(), OPENSSL_RAW_DATA, $nonce, $tag);

    if ($ciphertext === false) {
        throw new RuntimeException('Cifratura dati non riuscita.');
    }

    return ENCRYPTED_VALUE_PREFIX . base64_encode($nonce . $tag . $ciphertext);
}

function decrypt_value(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    if (!str_starts_with($value, ENCRYPTED_VALUE_PREFIX)) {
        return $value;
    }

    $payload = base64_decode(substr($value, strlen(ENCRYPTED_VALUE_PREFIX)), true);
    if ($payload === false || strlen($payload) < 29) {
        return '';
    }

    $nonce = substr($payload, 0, 12);
    $tag = substr($payload, 12, 16);
    $ciphertext = substr($payload, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', app_encryption_key(), OPENSSL_RAW_DATA, $nonce, $tag);

    return $plaintext === false ? '' : $plaintext;
}

<?php
declare(strict_types=1);

require __DIR__ . '/../src/auth.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Questo script va eseguito da terminale.\n");
    exit(1);
}

$password = '';
$clearLocksOnly = false;

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--clear-locks') {
        $clearLocksOnly = true;
        continue;
    }

    if (str_starts_with($argument, '--password=')) {
        $password = substr($argument, strlen('--password='));
    }
}

$rateLimitFile = admin_login_rate_limit_file();

if ($clearLocksOnly) {
    file_put_contents($rateLimitFile, '[]');
    echo "Tentativi admin azzerati.\n";
    exit(0);
}

if ($password === '') {
    echo "Nuova password admin: ";
    $password = trim((string) fgets(STDIN));
}

if (strlen($password) < 8) {
    fwrite(STDERR, "La password deve avere almeno 8 caratteri.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$envPath = __DIR__ . '/../.env';
$envContents = is_file($envPath) ? file_get_contents($envPath) : '';

if ($envContents === false) {
    fwrite(STDERR, "Impossibile leggere .env.\n");
    exit(1);
}

$line = 'ADMIN_PASSWORD_HASH=' . $hash;

if (preg_match('/^ADMIN_PASSWORD_HASH=.*$/m', $envContents)) {
    $envContents = preg_replace_callback('/^ADMIN_PASSWORD_HASH=.*$/m', static function () use ($line): string {
        return $line;
    }, $envContents);
} else {
    $envContents = rtrim($envContents) . "\n" . $line . "\n";
}

file_put_contents($envPath, $envContents);
file_put_contents($rateLimitFile, '[]');

echo "Password admin aggiornata in .env e tentativi azzerati.\n";

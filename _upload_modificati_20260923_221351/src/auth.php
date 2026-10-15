<?php
declare(strict_types=1);

const ADMIN_SESSION_IDLE_SECONDS = 1800;
const ADMIN_LOGIN_MAX_ATTEMPTS = 5;
const ADMIN_LOGIN_WINDOW_SECONDS = 900;
const ADMIN_LOGIN_LOCK_SECONDS = 900;
const ADMIN_DEFAULT_PASSWORD_HASH = '$2y$10$uvpfrCAaqr/lLLDBpbFpsuXg7iEHTur82Ow9MIdKmYyVu.ku7Q8Ty';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

send_admin_security_headers();

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function send_admin_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
}

function is_admin_logged_in(): bool
{
    if (empty($_SESSION['admin_logged_in'])) {
        return false;
    }

    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
    if ($lastActivity > 0 && time() - $lastActivity > ADMIN_SESSION_IDLE_SECONDS) {
        admin_logout();
        return false;
    }

    $_SESSION['admin_last_activity'] = time();
    return true;
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: /public/admin/login.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');

    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessione scaduta. Ricarica la pagina e riprova.');
    }
}

function admin_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function admin_is_local_request(): bool
{
    $remoteAddress = admin_client_ip();
    $serverName = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));

    return in_array($remoteAddress, ['127.0.0.1', '::1', 'unknown'], true)
        || in_array($serverName, ['localhost', '127.0.0.1'], true);
}

function admin_default_password_is_blocked(): bool
{
    $config = require __DIR__ . '/../config/admin.php';

    return hash_equals(ADMIN_DEFAULT_PASSWORD_HASH, $config['password_hash']) && !admin_is_local_request();
}

function admin_login_rate_limit_file(): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'unfaired_admin_login_attempts.json';
}

function admin_login_attempt_key(string $username): string
{
    return hash('sha256', strtolower(trim($username)) . '|' . admin_client_ip());
}

function admin_login_rate_limit_status(string $username): array
{
    $key = admin_login_attempt_key($username);
    $now = time();
    $status = [
        'locked' => false,
        'seconds_remaining' => 0,
    ];

    $file = admin_login_rate_limit_file();
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        return $status;
    }

    try {
        flock($handle, LOCK_EX);
        $contents = stream_get_contents($handle);
        $attempts = $contents ? json_decode($contents, true) : [];
        if (!is_array($attempts)) {
            $attempts = [];
        }

        foreach ($attempts as $attemptKey => $attempt) {
            if (!is_array($attempt) || (int) ($attempt['expires_at'] ?? 0) <= $now) {
                unset($attempts[$attemptKey]);
            }
        }

        $attempt = $attempts[$key] ?? null;
        if (is_array($attempt) && (int) ($attempt['locked_until'] ?? 0) > $now) {
            $status = [
                'locked' => true,
                'seconds_remaining' => (int) $attempt['locked_until'] - $now,
            ];
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($attempts));
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    return $status;
}

function admin_register_failed_login(string $username): void
{
    $key = admin_login_attempt_key($username);
    $now = time();
    $file = admin_login_rate_limit_file();
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        return;
    }

    try {
        flock($handle, LOCK_EX);
        $contents = stream_get_contents($handle);
        $attempts = $contents ? json_decode($contents, true) : [];
        if (!is_array($attempts)) {
            $attempts = [];
        }

        foreach ($attempts as $attemptKey => $attempt) {
            if (!is_array($attempt) || (int) ($attempt['expires_at'] ?? 0) <= $now) {
                unset($attempts[$attemptKey]);
            }
        }

        $attempt = $attempts[$key] ?? [
            'count' => 0,
            'first_at' => $now,
            'locked_until' => 0,
            'expires_at' => $now + ADMIN_LOGIN_WINDOW_SECONDS,
        ];

        if ($now - (int) ($attempt['first_at'] ?? $now) > ADMIN_LOGIN_WINDOW_SECONDS) {
            $attempt = [
                'count' => 0,
                'first_at' => $now,
                'locked_until' => 0,
                'expires_at' => $now + ADMIN_LOGIN_WINDOW_SECONDS,
            ];
        }

        $attempt['count'] = (int) ($attempt['count'] ?? 0) + 1;
        $attempt['expires_at'] = $now + ADMIN_LOGIN_WINDOW_SECONDS;

        if ($attempt['count'] >= ADMIN_LOGIN_MAX_ATTEMPTS) {
            $attempt['locked_until'] = $now + ADMIN_LOGIN_LOCK_SECONDS;
            $attempt['expires_at'] = $now + ADMIN_LOGIN_LOCK_SECONDS;
        }

        $attempts[$key] = $attempt;

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($attempts));
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function admin_clear_failed_logins(string $username): void
{
    $key = admin_login_attempt_key($username);
    $file = admin_login_rate_limit_file();
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        return;
    }

    try {
        flock($handle, LOCK_EX);
        $contents = stream_get_contents($handle);
        $attempts = $contents ? json_decode($contents, true) : [];
        if (!is_array($attempts)) {
            $attempts = [];
        }

        unset($attempts[$key]);

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($attempts));
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function attempt_admin_login(string $username, string $password): bool
{
    $config = require __DIR__ . '/../config/admin.php';
    $dummyHash = '$2y$10$7EqJtq98hPqEX7fNZaFWoOhiS5h/FQS.2NmGQmLJ1nhmC2xOsU1km';

    if (admin_default_password_is_blocked()) {
        admin_register_failed_login($username);
        return false;
    }

    if (!hash_equals($config['username'], $username)) {
        password_verify($password, $dummyHash);
        admin_register_failed_login($username);
        return false;
    }

    if (!password_verify($password, $config['password_hash'])) {
        admin_register_failed_login($username);
        return false;
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_login_ip'] = admin_client_ip();
    admin_clear_failed_logins($username);

    return true;
}

function admin_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

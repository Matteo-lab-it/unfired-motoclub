<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [];

$checks[] = ['PHP 8.0+', version_compare(PHP_VERSION, '8.0.0', '>=')];
$checks[] = ['PDO extension', extension_loaded('pdo')];
$checks[] = ['PDO MySQL extension', extension_loaded('pdo_mysql')];
$checks[] = ['getimagesize available for image upload checks', function_exists('getimagesize')];
$checks[] = ['Config database readable', is_readable($root . '/config/database.php')];
$checks[] = ['Config admin readable', is_readable($root . '/config/admin.php')];
$checks[] = ['Shared source readable', is_readable($root . '/src/db.php') && is_readable($root . '/src/auth.php')];

foreach (['public/uploads/events', 'public/uploads/shop'] as $directory) {
    $path = $root . '/' . $directory;
    $checks[] = [$directory . ' exists', is_dir($path)];
    $checks[] = [$directory . ' writable', is_dir($path) && is_writable($path)];
}

if (is_readable($root . '/src/db.php')) {
    try {
        require_once $root . '/src/db.php';
        db()->query('SELECT 1');
        $checks[] = ['Database connection', true];
    } catch (Throwable $exception) {
        $checks[] = ['Database connection (' . $exception->getMessage() . ')', false];
    }
}

$failed = false;

foreach ($checks as [$label, $ok]) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    $failed = $failed || !$ok;
}

exit($failed ? 1 : 0);

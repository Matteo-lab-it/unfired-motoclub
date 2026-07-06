<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/env.php';

return [
    // Default XAMPP locale. In hosting puoi sovrascrivere questi valori con variabili ambiente.
    'host' => env_value('DB_HOST', 'localhost'),
    'port' => (int) env_value('DB_PORT', '3306'),
    'database' => env_value('DB_DATABASE', 'unfired_moto_club'),
    'username' => env_value('DB_USERNAME', 'root'),
    'password' => env_value('DB_PASSWORD', ''),
    'charset' => env_value('DB_CHARSET', 'utf8mb4'),
];

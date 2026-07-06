<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/env.php';

return [
    'username' => env_value('ADMIN_USERNAME', 'admin'),
    'password_hash' => env_value('ADMIN_PASSWORD_HASH', '$2y$10$uvpfrCAaqr/lLLDBpbFpsuXg7iEHTur82Ow9MIdKmYyVu.ku7Q8Ty'),
];

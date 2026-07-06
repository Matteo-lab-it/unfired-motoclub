<?php
declare(strict_types=1);

require __DIR__ . '/../../src/auth.php';

admin_logout();

header('Location: /admin/login.php');
exit;

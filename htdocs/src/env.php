<?php
declare(strict_types=1);

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));

        if ($name === '') {
            continue;
        }

        $value = trim($value, "\"'");
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function env_value(string $name, string $fallback): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $fallback : $value;
}

load_env_file(__DIR__ . '/../.env');

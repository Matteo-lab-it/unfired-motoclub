<?php
declare(strict_types=1);

function public_api_cache_file(string $key): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'unfaired_api_cache_' . hash('sha256', $key) . '.json';
}

function public_api_serve_cached_json(string $key, int $ttlSeconds): void
{
    $file = public_api_cache_file($key);

    if (!is_file($file) || filemtime($file) + max(1, $ttlSeconds) < time()) {
        return;
    }

    $contents = file_get_contents($file);
    if ($contents === false || $contents === '') {
        return;
    }

    header('X-Cache: HIT');
    echo $contents;
    exit;
}

function public_api_store_cached_json(string $key, string $json): void
{
    if ($json === '') {
        return;
    }

    $file = public_api_cache_file($key);
    $temporaryFile = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';

    if (file_put_contents($temporaryFile, $json, LOCK_EX) === false) {
        return;
    }

    rename($temporaryFile, $file);
}

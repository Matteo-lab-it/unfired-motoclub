<?php
declare(strict_types=1);

function rate_limit_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function rate_limit_file(): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'unfaired_public_rate_limits.json';
}

function public_api_rate_limit(string $scope, int $maxRequests, int $windowSeconds): void
{
    $now = time();
    $windowSeconds = max(1, $windowSeconds);
    $maxRequests = max(1, $maxRequests);
    $key = hash('sha256', $scope . '|' . rate_limit_client_ip());
    $file = rate_limit_file();
    $handle = fopen($file, 'c+');

    if ($handle === false) {
        return;
    }

    try {
        flock($handle, LOCK_EX);
        $contents = stream_get_contents($handle);
        $limits = $contents ? json_decode($contents, true) : [];

        if (!is_array($limits)) {
            $limits = [];
        }

        foreach ($limits as $limitKey => $limit) {
            if (!is_array($limit) || (int) ($limit['reset_at'] ?? 0) <= $now) {
                unset($limits[$limitKey]);
            }
        }

        $limit = $limits[$key] ?? [
            'count' => 0,
            'reset_at' => $now + $windowSeconds,
        ];

        if ((int) ($limit['reset_at'] ?? 0) <= $now) {
            $limit = [
                'count' => 0,
                'reset_at' => $now + $windowSeconds,
            ];
        }

        $limit['count'] = (int) ($limit['count'] ?? 0) + 1;
        $limits[$key] = $limit;

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($limits));

        if ($limit['count'] > $maxRequests) {
            $retryAfter = max(1, (int) $limit['reset_at'] - $now);
            header('Retry-After: ' . $retryAfter);
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Troppe richieste. Riprova tra qualche minuto.',
            ]);
            exit;
        }
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

function public_shop_image_path(?string $path): string
{
    $path = (string) $path;

    if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return $path;
    }

    return '/' . $path;
}

try {
    $statement = db()->query(
        'SELECT id, name, description, sizes, image_path
         FROM shop_products
         WHERE is_published = 1
         ORDER BY sort_order ASC, id DESC'
    );

    $products = array_map(static function (array $product): array {
        $sizes = array_values(array_filter(array_map('trim', explode(',', (string) $product['sizes']))));

        return [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'sizes' => $sizes,
            'image_url' => public_shop_image_path($product['image_path']),
        ];
    }, $statement->fetchAll());

    echo json_encode([
        'success' => true,
        'products' => $products,
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'products' => [],
    ]);
}

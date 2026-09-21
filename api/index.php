<?php

declare(strict_types=1);

use Pizza\Catalog;
use Pizza\Db;
use Pizza\Oven;
use Pizza\ValidationError;

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    // The app has no runtime dependencies, so it also runs without composer install.
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'Pizza\\')) {
            $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function send(mixed $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

/** A random but legal pizza, for people who cannot decide. */
function surprise(): array
{
    $pick = static function (array $options, int $n): array {
        shuffle($options);

        return array_column(array_slice($options, 0, $n), 'id');
    };

    $selection = [];
    foreach (Catalog::LAYERS as $layer) {
        $count = match ($layer['id']) {
            'toppings' => random_int(2, 3),
            'finish' => random_int(0, 1),
            'cheese' => random_int(1, 2),
            default => 1,
        };
        $selection[$layer['id']] = $pick($layer['options'], $count);
    }

    return $selection;
}

$oven = new Oven();

switch (true) {
    case $method === 'GET' && in_array($path, ['/', '/health'], true):
        send([
            'service' => 'pizza-generator-api',
            'status' => 'hot',
            'php' => PHP_VERSION,
            'storage' => (new Db())->driver(),
        ]);

    case $method === 'GET' && $path === '/ingredients':
        send(['layers' => Catalog::LAYERS]);

    case $method === 'GET' && $path === '/pizzas':
        send(['pizzas' => (new Db())->recent()]);

    case $method === 'GET' && $path === '/surprise':
        send(['selection' => surprise()]);

    case $method === 'POST' && $path === '/pizzas':
        $payload = body();
        try {
            $pizza = $oven->bake($payload['selection'] ?? [], (string) ($payload['chef'] ?? ''));
        } catch (ValidationError $e) {
            send(['error' => 'invalid_pizza', 'issues' => $e->errors], 422);
        }

        send(['pizza' => (new Db())->save($pizza)], 201);

    default:
        send(['error' => 'not_found', 'path' => $path], 404);
}

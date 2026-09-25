<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

ini_set('display_errors', config('debug') ? '1' : '0');
error_reporting(E_ALL);

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
]);
session_name('siakad_sid');
session_start();

$routes = require BASE_PATH . '/app/routes.php';
$route  = trim(param('r'), '/');

if (!isset($routes[$route])) {
    require_login();
    abort(404, 'Halaman yang Anda cari tidak ditemukan.');
}

[$file, $handler, $roles] = $routes[$route];

if ($roles !== null) {
    $roles === [] ? require_login() : require_role(...$roles);
}

verify_csrf();

try {
    require_once BASE_PATH . '/app/controllers/' . $file . '.php';
    $handler();
} catch (Throwable $e) {
    error_log((string) $e);
    if (config('debug')) {
        throw $e;
    }
    abort(500, 'Terjadi kesalahan pada server. Silakan coba lagi.');
}

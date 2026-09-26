<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('<!doctype html><meta charset="utf-8"><title>Versi PHP terlalu lama</title>'
        . '<div style="font-family:sans-serif;max-width:620px;margin:60px auto;line-height:1.6">'
        . '<h2 style="color:#b91c1c">Versi PHP terlalu lama (' . PHP_VERSION . ')</h2>'
        . '<p>SIAKAD membutuhkan <b>PHP 8.1 atau lebih baru</b>. Gunakan XAMPP versi 8.2 atau yang lebih baru, '
        . 'atau pilih versi PHP yang lebih baru di panel hosting.</p></div>');
}

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

try {
    if (!isset($routes[$route])) {
        require_login();
        abort(404, 'Halaman yang Anda cari tidak ditemukan.');
    }

    [$file, $handler, $roles] = $routes[$route];

    if ($roles !== null) {
        $roles === [] ? require_login() : require_role(...$roles);
    }

    verify_csrf();

    require_once BASE_PATH . '/app/controllers/' . $file . '.php';
    $handler();
} catch (Throwable $e) {
    // Database ada tetapi tabel belum dibuat (belum di-import) -> halaman instalasi di localhost
    if ($e instanceof PDOException && ($e->errorInfo[0] ?? $e->getCode()) === '42S02' && is_local_request()) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Location: setup.php');
        exit;
    }
    error_log((string) $e);
    if (config('debug')) {
        throw $e;
    }
    abort(500, 'Terjadi kesalahan pada server. Silakan coba lagi.');
}

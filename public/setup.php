<?php
/**
 * Instalasi database lewat browser — khusus komputer lokal (XAMPP/Laragon).
 *
 * Buka http://localhost/siakad/setup.php, pilih jenis data, lalu klik "Pasang Database".
 * Halaman ini hanya melayani permintaan dari komputer itu sendiri (127.0.0.1 / ::1)
 * dan menolak bekerja bila database sudah terpasang, sehingga aman ikut ter-upload ke hosting.
 */
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('<!doctype html><meta charset="utf-8"><title>Versi PHP terlalu lama</title>'
        . '<div style="font-family:sans-serif;max-width:620px;margin:60px auto;line-height:1.6">'
        . '<h2 style="color:#b91c1c">Versi PHP terlalu lama (' . PHP_VERSION . ')</h2>'
        . '<p>SIAKAD membutuhkan <b>PHP 8.1 atau lebih baru</b>. Pasang XAMPP versi 8.2 atau yang lebih baru '
        . 'dari apachefriends.org, lalu salin ulang folder aplikasi ke <code>htdocs</code>.</p></div>');
}

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
session_name('siakad_setup');
session_start();

const SETUP_FILES = [
    'demo'   => ['sekolah_mysql.sql', 'Data contoh (untuk mencoba)',
                 '57 akun contoh untuk ketujuh peran, lengkap dengan nilai, absensi, tagihan, dan pengumuman.'],
    'kosong' => ['sekolah_mysql_kosong.sql', 'Database kosong (untuk dipakai sungguhan)',
                 'Hanya berisi 1 akun admin. Data sekolah diisi sendiri setelah login.'],
];

/** Koneksi ke server MySQL tanpa memilih database. */
function setup_server(array $c): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $c['host'], $c['port']);
    return new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function setup_state(PDO $pdo, string $name): array
{
    $exists = (bool) $pdo->query('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '
        . $pdo->quote($name))->fetchColumn();
    $installed = $exists && (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = "
        . $pdo->quote($name) . " AND TABLE_NAME = 'users'")->fetchColumn();
    return [$exists, $installed];
}

/** Menjalankan file dump SQL pernyataan demi pernyataan. */
function setup_import(PDO $pdo, string $file): int
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("File $file tidak dapat dibaca.");
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $count = 0;
    foreach (preg_split('/;\s*$/m', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
            $count++;
        }
    }
    return $count;
}

$c = config('db');
$errors = [];
$done = null;
$state = [false, false];
$pdo = null;

if (!is_local_request()) {
    http_response_code(403);
    $errors[] = 'Halaman instalasi hanya dapat dibuka dari komputer server itu sendiri (localhost). '
        . 'Untuk hosting, ikuti Panduan Instalasi cPanel.';
} elseif ($c['driver'] !== 'mysql') {
    $errors[] = 'Halaman ini untuk MySQL/MariaDB. Untuk SQLite jalankan: php database/install.php --demo';
} elseif (!extension_loaded('pdo_mysql')) {
    $errors[] = 'Ekstensi PHP pdo_mysql belum aktif. Aktifkan "extension=pdo_mysql" di php.ini, lalu restart Apache.';
} elseif (!preg_match('/^[A-Za-z0-9_]+$/', $c['name'])) {
    $errors[] = 'Nama database di konfigurasi hanya boleh berisi huruf, angka, dan garis bawah.';
} else {
    try {
        $pdo = setup_server($c);
        $state = setup_state($pdo, $c['name']);
    } catch (PDOException $e) {
        $errors[] = 'Tidak dapat terhubung ke MySQL di ' . $c['host'] . ':' . $c['port'] . ' dengan user "' . $c['user'] . '". '
            . 'Pastikan modul MySQL sudah di-Start di XAMPP Control Panel. (' . $e->getMessage() . ')';
    }
}

if ($pdo && is_post() && !$errors) {
    $token = $_POST['_csrf'] ?? '';
    $mode = $_POST['mode'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['setup_csrf'] ?? '', $token)) {
        $errors[] = 'Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif (!isset(SETUP_FILES[$mode])) {
        $errors[] = 'Pilih jenis data yang akan dipasang.';
    } elseif ($state[1]) {
        $errors[] = 'Database sudah terpasang sebelumnya. Tidak ada yang diubah.';
    } else {
        try {
            $name = $c['name'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");
            $n = setup_import($pdo, BASE_PATH . '/database/' . SETUP_FILES[$mode][0]);
            $done = ['mode' => $mode, 'statements' => $n];
            $state = setup_state($pdo, $name);
        } catch (Throwable $e) {
            $errors[] = 'Instalasi gagal: ' . $e->getMessage();
        }
    }
}

$_SESSION['setup_csrf'] ??= bin2hex(random_bytes(32));
$phpOk = PHP_VERSION_ID >= 80100;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalasi · SIAKAD Sekolah</title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<div class="login-wrap d-flex align-items-center justify-content-center p-3">
    <div class="card shadow-lg border-0" style="max-width: 640px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="display-6 text-primary"><i class="bi bi-mortarboard-fill"></i></div>
                <h1 class="h4 fw-bold mb-1">Instalasi SIAKAD Sekolah</h1>
                <p class="text-muted small mb-0">Pemasangan database di komputer lokal (XAMPP)</p>
            </div>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger small"><i class="bi bi-exclamation-triangle"></i> <?= e($err) ?></div>
            <?php endforeach; ?>

            <?php if ($done): ?>
                <div class="alert alert-success">
                    <div class="fw-semibold mb-1"><i class="bi bi-check-circle"></i> Database berhasil dipasang!</div>
                    <div class="small"><?= (int) $done['statements'] ?> perintah SQL dijalankan pada database
                        <code><?= e($c['name']) ?></code>.</div>
                </div>
                <?php if ($done['mode'] === 'demo'): ?>
                    <p class="small mb-2">Semua akun contoh memakai password <strong><code>password123</code></strong>:</p>
                    <table class="table table-sm small">
                        <tr><td>Administrasi</td><td><code>admin</code></td></tr>
                        <tr><td>Kepala Sekolah</td><td><code>kepsek</code></td></tr>
                        <tr><td>Tata Usaha</td><td><code>tu</code></td></tr>
                        <tr><td>Guru</td><td><code>guru1</code> … <code>guru4</code></td></tr>
                        <tr><td>Wali Kelas</td><td><code>walikelas1</code> … <code>walikelas3</code></td></tr>
                        <tr><td>Murid</td><td><code>siswa1</code> … <code>siswa24</code></td></tr>
                        <tr><td>Orang Tua</td><td><code>ortu1</code> … <code>ortu23</code></td></tr>
                    </table>
                <?php else: ?>
                    <p class="small">Masuk dengan username <strong><code>admin</code></strong> dan password
                        <strong><code>admin12345</code></strong>, lalu segera ganti password di menu Profil.</p>
                <?php endif; ?>
                <a class="btn btn-primary w-100" href="index.php"><i class="bi bi-box-arrow-in-right"></i> Buka Aplikasi</a>

            <?php elseif (is_local_request()): ?>
                <ul class="list-group mb-4 small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Versi PHP</span>
                        <span class="<?= $phpOk ? 'text-success' : 'text-danger' ?>"><?= e(PHP_VERSION) ?> <?= $phpOk ? '✓' : '✗' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Koneksi MySQL (<?= e($c['host'] . ':' . $c['port']) ?>, user <?= e($c['user']) ?>)</span>
                        <span class="<?= $pdo ? 'text-success' : 'text-danger' ?>"><?= $pdo ? 'Terhubung ✓' : 'Gagal ✗' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Database <code><?= e($c['name']) ?></code></span>
                        <span><?= $state[1] ? '<span class="text-success">Sudah terpasang ✓</span>'
                            : ($state[0] ? 'Ada, masih kosong' : 'Belum ada (akan dibuat)') ?></span>
                    </li>
                </ul>

                <?php if ($state[1]): ?>
                    <div class="alert alert-info small">Database sudah terpasang. Untuk memasang ulang, hapus database
                        <code><?= e($c['name']) ?></code> di phpMyAdmin terlebih dahulu.</div>
                    <a class="btn btn-primary w-100" href="index.php"><i class="bi bi-box-arrow-in-right"></i> Buka Aplikasi</a>
                <?php elseif ($pdo): ?>
                    <form method="post">
                        <input type="hidden" name="_csrf" value="<?= e($_SESSION['setup_csrf']) ?>">
                        <div class="mb-3 fw-semibold">Pilih jenis data:</div>
                        <?php foreach (SETUP_FILES as $key => [$file, $label, $desc]): ?>
                            <label class="border rounded p-3 mb-2 d-flex gap-2 w-100" style="cursor:pointer">
                                <input class="form-check-input mt-1" type="radio" name="mode" value="<?= e($key) ?>" <?= $key === 'demo' ? 'checked' : '' ?>>
                                <span><span class="fw-semibold"><?= e($label) ?></span><br><span class="small text-muted"><?= e($desc) ?></span></span>
                            </label>
                        <?php endforeach; ?>
                        <button class="btn btn-primary w-100 mt-2 py-2"><i class="bi bi-database-add"></i> Pasang Database</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-primary w-100" href="setup.php"><i class="bi bi-arrow-clockwise"></i> Coba Lagi</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

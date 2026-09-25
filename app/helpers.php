<?php
declare(strict_types=1);

const ROLES = [
    'admin'          => 'Administrasi',
    'kepala_sekolah' => 'Kepala Sekolah',
    'tata_usaha'     => 'Tata Usaha',
    'guru'           => 'Guru',
    'wali_kelas'     => 'Wali Kelas',
    'siswa'          => 'Murid',
    'orang_tua'      => 'Orang Tua Murid',
];

const TEACHER_ROLES = ['guru', 'wali_kelas'];

const ATTENDANCE_STATUS = [
    'H' => 'Hadir',
    'S' => 'Sakit',
    'I' => 'Izin',
    'A' => 'Alpa',
];

const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
    'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const PAYMENT_METHODS = ['Tunai', 'Transfer Bank', 'QRIS', 'Virtual Account'];

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function url(string $route = '', array $params = []): string
{
    $query = $route !== '' ? ['r' => $route] + $params : $params;
    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $route = '', array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function back(string $fallbackRoute = ''): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    // Hanya kembali ke halaman internal aplikasi
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? null)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallbackRoute);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if (!is_post()) {
        return;
    }
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        abort(419, 'Sesi formulir telah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function input_array(string $key): array
{
    $v = $_POST[$key] ?? [];
    return is_array($v) ? $v : [];
}

function param(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function param_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? null;
    return is_string($v) && ctype_digit($v) ? (int) $v : $default;
}

function nullable(string $v): ?string
{
    return $v === '' ? null : $v;
}

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function render(string $view, array $data = [], string $title = ''): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/app/views/' . $view . '.php';
    $content = ob_get_clean();
    $pageTitle = $title;
    require BASE_PATH . '/app/views/layout.php';
}

function abort(int $code, string $message = ''): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    $titles = [403 => 'Akses Ditolak', 404 => 'Halaman Tidak Ditemukan', 419 => 'Sesi Kedaluwarsa', 500 => 'Terjadi Kesalahan'];
    $title = $titles[$code] ?? 'Kesalahan';
    if (session_status() === PHP_SESSION_ACTIVE && current_user()) {
        render('shared/error', ['code' => $code, 'message' => $message ?: $title], $title);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title>'
            . '<div style="font-family:sans-serif;max-width:560px;margin:80px auto;text-align:center">'
            . '<h1>' . $code . '</h1><p>' . e($message ?: $title) . '</p><a href="index.php">Kembali</a></div>';
    }
    exit;
}

function role_label(string $role): string
{
    return ROLES[$role] ?? $role;
}

function rupiah(mixed $n): string
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

function tanggal(?string $date, bool $withDay = false): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return e($date);
    }
    $out = date('j', $ts) . ' ' . MONTHS[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withDay) {
        $names = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $out = $names[(int) date('w', $ts)] . ', ' . $out;
    }
    return $out;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db_all('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = (string) $row['svalue'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    if (db_value('SELECT COUNT(*) FROM settings WHERE skey = ?', [$key])) {
        db_query('UPDATE settings SET svalue = ? WHERE skey = ?', [$value, $key]);
    } else {
        db_query('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
    }
}

/** Tahun ajaran & semester aktif. */
function period(): array
{
    return [
        'year'     => setting('academic_year', date('Y') . '/' . (date('Y') + 1)),
        'semester' => setting('semester', 'Ganjil'),
    ];
}

/**
 * Nilai akhir berbobot. Komponen yang belum diisi tidak dihitung sebagai 0;
 * bobot dinormalisasi terhadap komponen yang sudah ada.
 */
function score_final(mixed $task, mixed $mid, mixed $final): ?float
{
    $w = config('grade_weights');
    $sum = 0.0;
    $weight = 0;
    foreach (['task' => $task, 'mid' => $mid, 'final' => $final] as $k => $v) {
        if ($v !== null && $v !== '') {
            $sum += (float) $v * $w[$k];
            $weight += $w[$k];
        }
    }
    return $weight ? round($sum / $weight, 2) : null;
}

function predicate(?float $score): string
{
    if ($score === null) {
        return '-';
    }
    return match (true) {
        $score >= 90 => 'A',
        $score >= 80 => 'B',
        $score >= 70 => 'C',
        default      => 'D',
    };
}

function fmt_score(mixed $v): string
{
    if ($v === null || $v === '') {
        return '-';
    }
    $f = (float) $v;
    return floor($f) == $f ? (string) (int) $f : number_format($f, 1, ',', '.');
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function status_badge(string $status): string
{
    $map = ['H' => 'success', 'S' => 'info', 'I' => 'warning', 'A' => 'danger'];
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e(ATTENDANCE_STATUS[$status] ?? $status) . '</span>';
}

function invoice_badge(float $amount, float $paid): string
{
    if ($paid >= $amount) {
        return '<span class="badge text-bg-success">Lunas</span>';
    }
    if ($paid > 0) {
        return '<span class="badge text-bg-warning">Sebagian</span>';
    }
    return '<span class="badge text-bg-danger">Belum Bayar</span>';
}

function stat_card(string $label, string|int $value, string $icon, string $color = 'primary', ?string $link = null): string
{
    $body = '<div class="card stat-card shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">'
        . '<div class="stat-icon bg-' . e($color) . '-subtle text-' . e($color) . '"><i class="bi bi-' . e($icon) . '"></i></div>'
        . '<div><div class="text-muted small">' . e($label) . '</div><div class="fs-4 fw-bold">' . e($value) . '</div></div>'
        . '</div></div>';
    return $link ? '<a class="text-decoration-none text-reset" href="' . e($link) . '">' . $body . '</a>' : $body;
}

function empty_row(int $colspan, string $message = 'Belum ada data.'): string
{
    return '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-4">' . e($message) . '</td></tr>';
}

/** Tombol hapus dengan form POST + konfirmasi. */
function delete_button(string $route, int $id, string $confirm = 'Hapus data ini?', string $label = ''): string
{
    return '<form method="post" action="' . e(url($route)) . '" class="d-inline" data-confirm="' . e($confirm) . '">'
        . csrf_field() . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i>' . ($label ? ' ' . e($label) : '') . '</button></form>';
}

function visible_announcements(string $role, int $limit = 0): array
{
    $sql = "SELECT a.*, u.name AS author FROM announcements a LEFT JOIN users u ON u.id = a.created_by
            WHERE a.audience IN ('semua', ?)";
    $params = [$role];
    if (in_array($role, ['admin', 'kepala_sekolah', 'tata_usaha'], true)) {
        $sql = 'SELECT a.*, u.name AS author FROM announcements a LEFT JOIN users u ON u.id = a.created_by WHERE 1 = 1';
        $params = [];
    } elseif (in_array($role, TEACHER_ROLES, true)) {
        $sql .= " OR a.audience = 'guru_semua'";
    }
    $sql .= ' ORDER BY a.created_at DESC, a.id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    return db_all($sql, $params);
}

/** "Rp 350.000" / "350000" -> 350000.0 (rupiah bulat) */
function parse_rupiah(string $v): float
{
    return (float) preg_replace('/\D/', '', $v);
}

<?php
declare(strict_types=1);

/**
 * Smoke test end-to-end: menjalankan server PHP bawaan dengan database demo sementara,
 * login sebagai setiap peran, membuka semua menu, dan menguji alur input utama.
 *
 *   php tests/smoke_test.php
 */

$root = dirname(__DIR__);
$db = sys_get_temp_dir() . '/siakad_test_' . getmypid() . '.sqlite';
$env = 'DB_DRIVER=sqlite DB_SQLITE_PATH=' . escapeshellarg($db) . ' APP_DEBUG=true';

passthru("$env php " . escapeshellarg("$root/database/install.php") . ' --demo --fresh > /dev/null', $rc);
if ($rc !== 0) {
    exit("Instalasi database gagal\n");
}

$port = 8765 + mt_rand(0, 200);
$server = proc_open("$env exec php -S 127.0.0.1:$port -t " . escapeshellarg("$root/public"),
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
usleep(600000);
$base = "http://127.0.0.1:$port/index.php";

$failures = 0;
$checks = 0;

function req(string $method, string $url, array $data = [], ?string $jar = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    return [$code, $body, $final];
}

function check(bool $ok, string $label): void
{
    global $failures, $checks;
    $checks++;
    if (!$ok) {
        $failures++;
        echo "  ✗ $label\n";
    }
}

function page_ok(array $res): bool
{
    [$code, $body] = $res;
    return $code === 200 && !preg_match('/(Warning|Fatal error|Notice|Deprecated|Uncaught)\b.*?:/', $body);
}

function csrf(string $body): string
{
    preg_match('/name="_csrf" value="([a-f0-9]+)"/', $body, $m);
    return $m[1] ?? '';
}

function login(string $base, string $user, string $password = 'password123'): array
{
    $jar = tempnam(sys_get_temp_dir(), 'jar');
    [, $body] = req('GET', "$base?r=login", [], $jar);
    $res = req('POST', "$base?r=login", ['_csrf' => csrf($body), 'username' => $user, 'password' => $password], $jar);
    return [$jar, $res];
}

$roleUsers = [
    'admin' => 'admin', 'kepala_sekolah' => 'kepsek', 'tata_usaha' => 'tu', 'guru' => 'guru1',
    'wali_kelas' => 'walikelas1', 'siswa' => 'siswa1', 'orang_tua' => 'ortu1',
];

require "$root/app/helpers.php";
require "$root/app/menu.php";

$jars = [];
foreach ($roleUsers as $role => $username) {
    echo "• $role ($username)\n";
    [$jar, $res] = login($base, $username);
    $jars[$role] = $jar;
    check(page_ok($res) && str_contains($res[1], 'Selamat datang'), "login $username");
    foreach (menu_for($role) as $items) {
        foreach ($items as [$label, $route]) {
            $r = req('GET', "$base?r=" . urlencode($route), [], $jar);
            check(page_ok($r), "$role membuka $label ($route) -> HTTP {$r[0]}");
        }
    }
}

echo "• Pembatasan akses\n";
$denied = [
    ['siswa', 'admin/pengguna'], ['siswa', 'guru/nilai'], ['orang_tua', 'keuangan/tagihan'],
    ['guru', 'walikelas/kelas'], ['guru', 'admin/kelas'], ['tata_usaha', 'guru/absensi'],
    ['kepala_sekolah', 'admin/pengguna'], ['wali_kelas', 'keuangan/tagihan'],
];
foreach ($denied as [$role, $route]) {
    [$code] = req('GET', "$base?r=$route", [], $jars[$role]);
    check($code === 403, "$role harus ditolak di $route (dapat $code)");
}
// Siswa lain / anak orang lain
[$code] = req('GET', "$base?r=rapor&student_id=5", [], $jars['orang_tua']);
check($code === 200, 'ortu1 membuka rapor (id asing -> dialihkan ke anak sendiri)');
[$code] = req('GET', "$base?r=rapor&student_id=20", [], $jars['wali_kelas']);
check($code === 403, "walikelas1 tidak boleh melihat rapor siswa kelas VIII-A (dapat $code)");
[$code] = req('GET', "$base?r=keuangan/kwitansi&id=40", [], $jars['siswa']);
check($code === 403 || $code === 404, "siswa1 tidak boleh melihat kwitansi siswa lain (dapat $code)");
[$code, , $final] = req('GET', "$base?r=admin/pengguna");
check(str_contains($final, 'r=login'), 'tamu dialihkan ke login');

echo "• CSRF\n";
[$code] = req('POST', "$base?r=pengumuman/form", ['title' => 'x', 'body' => 'y', 'audience' => 'semua'], $jars['admin']);
check($code === 419, "POST tanpa token CSRF ditolak (dapat $code)");

echo "• Alur input\n";
// Guru input nilai
[, $body] = req('GET', "$base?r=guru/nilai", [], $jars['guru']);
preg_match('/name="score\[(\d+)\]\[task_score\]"/', $body, $m);
preg_match('/<option value="(\d+)" selected/', $body, $a);
$res = req('POST', "$base?r=guru/nilai&assignment_id={$a[1]}", ['_csrf' => csrf($body),
    "score[{$m[1]}][task_score]" => '99', "score[{$m[1]}][mid_score]" => '98', "score[{$m[1]}][final_score]" => '97'], $jars['guru']);
check(page_ok($res) && str_contains($res[1], 'Nilai berhasil disimpan') && str_contains($res[1], 'value="99"'), 'guru menyimpan nilai');

// Wali kelas input absensi
[, $body] = req('GET', "$base?r=guru/absensi", [], $jars['wali_kelas']);
preg_match_all('/name="status\[(\d+)\]"/', $body, $mm);
$post = ['_csrf' => csrf($body)];
foreach (array_unique($mm[1]) as $sid) {
    $post["status[$sid]"] = 'H';
}
$res = req('POST', "$base?r=guru/absensi", $post, $jars['wali_kelas']);
check(page_ok($res) && str_contains($res[1], 'berhasil disimpan'), 'wali kelas menyimpan absensi');

// Wali kelas catatan rapor
[, $body] = req('GET', "$base?r=walikelas/catatan", [], $jars['wali_kelas']);
preg_match('/name="note\[(\d+)\]"/', $body, $m);
$res = req('POST', "$base?r=walikelas/catatan", ['_csrf' => csrf($body), "note[{$m[1]}]" => 'Catatan uji otomatis'], $jars['wali_kelas']);
check(page_ok($res) && str_contains($res[1], 'Catatan uji otomatis'), 'wali kelas menyimpan catatan rapor');

// TU membuat tagihan kelas & mencatat pembayaran
[, $body] = req('GET', "$base?r=keuangan/tagihan/form", [], $jars['tata_usaha']);
$res = req('POST', "$base?r=keuangan/tagihan/form", ['_csrf' => csrf($body), 'target' => 'class', 'class_id' => '1',
    'title' => 'Uang Buku Uji', 'amount' => '125.000', 'due_date' => date('Y-m-d')], $jars['tata_usaha']);
check(page_ok($res) && str_contains($res[1], '8 tagihan berhasil dibuat'), 'TU membuat tagihan satu kelas');
[, $list] = req('GET', "$base?r=keuangan/tagihan&q=" . urlencode('Uang Buku Uji'), [], $jars['tata_usaha']);
preg_match('/r=keuangan%2Fbayar&(?:amp;)?id=(\d+)/', $list, $m);
[, $body] = req('GET', "$base?r=keuangan/bayar&id={$m[1]}", [], $jars['tata_usaha']);
$res = req('POST', "$base?r=keuangan/bayar&id={$m[1]}", ['_csrf' => csrf($body), 'amount' => '999999999',
    'paid_at' => date('Y-m-d'), 'method' => 'Tunai'], $jars['tata_usaha']);
check(str_contains($res[1], 'tidak melebihi sisa'), 'TU tidak bisa membayar melebihi sisa tagihan');
$res = req('POST', "$base?r=keuangan/bayar&id={$m[1]}", ['_csrf' => csrf($res[1]), 'amount' => '125000',
    'paid_at' => date('Y-m-d'), 'method' => 'Tunai'], $jars['tata_usaha']);
check(page_ok($res) && str_contains($res[1], 'Lunas'), 'TU mencatat pembayaran hingga lunas');
preg_match('/r=keuangan%2Fkwitansi&(?:amp;)?id=(\d+)/', $res[1], $k);
check(page_ok(req('GET', "$base?r=keuangan/kwitansi&id={$k[1]}", [], $jars['tata_usaha'])), 'TU mencetak kwitansi');

// Kepala sekolah membuat pengumuman untuk orang tua, terlihat oleh ortu, tidak oleh siswa
[, $body] = req('GET', "$base?r=pengumuman/form", [], $jars['kepala_sekolah']);
req('POST', "$base?r=pengumuman/form", ['_csrf' => csrf($body), 'title' => 'Rapat Komite Uji', 'body' => 'Isi', 'audience' => 'orang_tua'], $jars['kepala_sekolah']);
check(str_contains(req('GET', "$base?r=pengumuman", [], $jars['orang_tua'])[1], 'Rapat Komite Uji'), 'ortu melihat pengumuman untuk orang tua');
check(!str_contains(req('GET', "$base?r=pengumuman", [], $jars['siswa'])[1], 'Rapat Komite Uji'), 'siswa tidak melihat pengumuman khusus orang tua');

// Admin menambah siswa baru dan jadwal bentrok ditolak
[, $body] = req('GET', "$base?r=admin/pengguna/form", [], $jars['admin']);
$res = req('POST', "$base?r=admin/pengguna/form", ['_csrf' => csrf($body), 'role' => 'siswa', 'name' => 'Siswa Baru Uji',
    'username' => 'siswabaru', 'password' => 'rahasia123', 'active' => '1', 'nis' => '20269999', 'class_id' => '2', 'gender' => 'L'], $jars['admin']);
check(page_ok($res) && str_contains($res[1], 'Siswa Baru Uji'), 'admin menambah siswa');
[$j2, $r2] = login($base, 'siswabaru', 'rahasia123');
check(str_contains($r2[1], 'Selamat datang'), 'siswa baru dapat login');
[, $body] = req('GET', "$base?r=admin/jadwal&class_id=1", [], $jars['admin']);
preg_match('/name="assignment_id"[^>]*>\s*<option value="(\d+)"/', $body, $m);
$res = req('POST', "$base?r=admin/jadwal&class_id=1", ['_csrf' => csrf($body), 'assignment_id' => $m[1], 'day' => '1',
    'start_time' => '07:30', 'end_time' => '08:00'], $jars['admin']);
check(str_contains($res[1], 'bentrok') || str_contains($res[1], 'sudah mengajar'), 'jadwal bentrok ditolak');

// Orang tua dengan 2 anak dapat berpindah anak
$res = req('GET', "$base?r=rapor&student_id=9", [], $jars['orang_tua']);
check(page_ok($res) && str_contains($res[1], 'VII-B'), 'ortu1 berpindah ke anak kedua (VII-B)');

// Ganti password
[, $body] = req('GET', "$base?r=profil", [], $j2);
$res = req('POST', "$base?r=profil", ['_csrf' => csrf($body), 'action' => 'password', 'old_password' => 'rahasia123',
    'new_password' => 'barubaru123', 'confirm_password' => 'barubaru123'], $j2);
check(str_contains($res[1], 'Password berhasil diganti'), 'pengguna mengganti password');

// Logout
[, $body] = req('GET', "$base", [], $j2);
[, , $final] = req('POST', "$base?r=logout", ['_csrf' => csrf($body)], $j2);
check(str_contains($final, 'r=login'), 'logout');

proc_terminate($server);
foreach ([$db, "$db-wal", "$db-shm"] as $f) {
    @unlink($f);
}
foreach ($jars as $j) {
    @unlink($j);
}

echo "\n$checks pemeriksaan, $failures gagal\n";
exit($failures ? 1 : 0);

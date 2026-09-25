<?php
declare(strict_types=1);

/**
 * Instalasi database.
 *
 *   php database/install.php            -> buat tabel + akun admin awal
 *   php database/install.php --demo     -> buat tabel + data contoh lengkap (semua peran)
 *   php database/install.php --fresh    -> hapus seluruh tabel/database lama terlebih dahulu (SQLite & MySQL)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jalankan dari command line.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$args = array_slice($argv, 1);
$demo = in_array('--demo', $args, true);
$fresh = in_array('--fresh', $args, true);
$driver = config('db.driver');

if ($driver === 'sqlite') {
    $path = config('db.sqlite_path');
    if ($fresh) {
        foreach ([$path, "$path-wal", "$path-shm"] as $f) {
            if (is_file($f)) {
                unlink($f);
            }
        }
    }
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
}

$pdo = db();
if ($fresh && $driver === 'mysql') {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (db_all('SHOW TABLES') as $row) {
        $pdo->exec('DROP TABLE `' . str_replace('`', '', (string) reset($row)) . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
$exists = $driver === 'sqlite'
    ? (bool) db_value("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users'")
    : (bool) db_value("SHOW TABLES LIKE 'users'");
if ($exists) {
    fwrite(STDERR, "Database sudah terpasang. Gunakan --fresh untuk menghapus & memasang ulang.\n");
    exit(1);
}

$pk = $driver === 'mysql' ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
$schema = str_replace('{PK}', $pk, file_get_contents(__DIR__ . '/schema.sql'));
$schema = preg_replace('/^\s*--.*$/m', '', $schema);
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($driver === 'mysql' && str_starts_with($stmt, 'CREATE TABLE')) {
        $stmt .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }
    $pdo->exec($stmt);
}
echo "✓ Tabel dibuat ($driver)\n";

$year = (int) date('n') >= 7 ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y');
$semester = (int) date('n') >= 7 ? 'Ganjil' : 'Genap';
foreach ([
    'school_name'    => $demo ? 'SMP Negeri 1 Nusantara' : 'Nama Sekolah',
    'school_address' => $demo ? 'Jl. Pendidikan No. 1, Jakarta' : '',
    'school_phone'   => $demo ? '(021) 555-0101' : '',
    'principal_name' => $demo ? 'Dr. Hendra Wijaya, M.Pd.' : '',
    'principal_nip'  => $demo ? '197001011995031001' : '',
    'academic_year'  => $year,
    'semester'       => $semester,
] as $k => $v) {
    set_setting($k, $v);
}

function make_user(string $username, string $name, string $role, string $password, array $extra = []): int
{
    return db_insert('users', [
        'username' => $username, 'name' => $name, 'role' => $role,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'active' => 1, 'created_at' => date('Y-m-d H:i:s'),
    ] + $extra);
}

if (!$demo) {
    $pass = bin2hex(random_bytes(5));
    make_user('admin', 'Administrator', 'admin', $pass);
    echo "✓ Akun admin dibuat\n  username: admin\n  password: $pass  (segera ganti setelah login)\n";
    exit(0);
}

/* ------------------------- DATA DEMO ------------------------- */
mt_srand(2026);
$pw = 'password123';

db_transaction(function () use ($pw, $year, $semester) {
    $admin = make_user('admin', 'Dewi Lestari', 'admin', $pw, ['nip' => '198505052010012001', 'email' => 'admin@sekolah.sch.id']);
    $kepsek = make_user('kepsek', 'Dr. Hendra Wijaya, M.Pd.', 'kepala_sekolah', $pw, ['nip' => '197001011995031001']);
    $tu = make_user('tu', 'Joko Susilo', 'tata_usaha', $pw, ['nip' => '198803032012011002', 'phone' => '081200000003']);

    $teachers = [
        'guru1'      => ['Budi Santoso, S.Pd.', 'guru'],
        'guru2'      => ['Siti Aminah, S.Pd.', 'guru'],
        'guru3'      => ['Ahmad Fauzi, S.Si.', 'guru'],
        'guru4'      => ['Maria Ulfa, S.Pd.', 'guru'],
        'walikelas1' => ['Rina Wati, S.Pd.', 'wali_kelas'],
        'walikelas2' => ['Agus Salim, S.Pd.', 'wali_kelas'],
        'walikelas3' => ['Nur Hasanah, S.Pd.', 'wali_kelas'],
    ];
    $tid = [];
    $n = 1;
    foreach ($teachers as $u => [$name, $role]) {
        $tid[$u] = make_user($u, $name, $role, $pw, ['nip' => sprintf('1980%02d%02d2008011%03d', $n, $n, $n), 'phone' => sprintf('08130000%04d', $n)]);
        $n++;
    }

    $classDefs = [['VII-A', 7, 'walikelas1'], ['VII-B', 7, 'walikelas2'], ['VIII-A', 8, 'walikelas3']];
    $cid = [];
    foreach ($classDefs as [$cname, $level, $wk]) {
        $cid[$cname] = db_insert('classes', ['name' => $cname, 'level' => $level, 'homeroom_teacher_id' => $tid[$wk]]);
    }

    $subjectDefs = [
        ['PAI', 'Pendidikan Agama Islam', 75, 'guru4'],
        ['PKN', 'Pendidikan Pancasila', 75, 'walikelas3'],
        ['BIN', 'Bahasa Indonesia', 75, 'guru2'],
        ['MTK', 'Matematika', 70, 'guru1'],
        ['IPA', 'Ilmu Pengetahuan Alam', 70, 'guru3'],
        ['IPS', 'Ilmu Pengetahuan Sosial', 75, 'walikelas2'],
        ['BIG', 'Bahasa Inggris', 75, 'walikelas1'],
    ];
    $assign = [];
    foreach ($subjectDefs as [$code, $name, $kkm, $teacher]) {
        $sid = db_insert('subjects', ['code' => $code, 'name' => $name, 'kkm' => $kkm]);
        foreach ($cid as $cname => $classId) {
            $assign[$cname][] = db_insert('teaching_assignments', ['teacher_id' => $tid[$teacher], 'class_id' => $classId, 'subject_id' => $sid]);
        }
    }

    // Jadwal: 7 mapel tersebar Senin–Sabtu, slot berbeda per kelas agar guru tidak bentrok
    $slots = [['07:00', '08:20'], ['08:20', '09:40'], ['10:00', '11:20'], ['11:20', '12:40']];
    foreach (array_keys($cid) as $ci => $cname) {
        $k = 0;
        foreach ($assign[$cname] as $ai => $aid) {
            for ($rep = 0; $rep < 2; $rep++) {
                $day = ($k % 6) + 1;
                $slot = $slots[(intdiv($k, 6) + $ci) % count($slots)];
                db_insert('schedules', ['assignment_id' => $aid, 'day' => $day, 'start_time' => $slot[0], 'end_time' => $slot[1], 'room' => 'R-' . $cname]);
                $k++;
            }
        }
    }

    $first = ['Andi', 'Bunga', 'Citra', 'Dimas', 'Eka', 'Fajar', 'Gita', 'Hadi', 'Indah', 'Joko', 'Kartika', 'Lukman',
        'Maya', 'Nanda', 'Oki', 'Putri', 'Rizky', 'Sari', 'Tono', 'Umi', 'Vina', 'Wahyu', 'Yusuf', 'Zahra'];
    $last = ['Pratama', 'Saputra', 'Wulandari', 'Hidayat', 'Kurniawan', 'Permata', 'Nugroho', 'Lestari', 'Ramadhan', 'Anggraini'];
    $parentFirst = ['Bapak Slamet', 'Ibu Wati', 'Bapak Darmawan', 'Ibu Yuliana', 'Bapak Rudi', 'Ibu Sri', 'Bapak Hasan', 'Ibu Ratna'];
    $studentIds = [];
    $i = 0;
    $parentCount = 0;
    foreach ($cid as $cname => $classId) {
        for ($j = 0; $j < 8; $j++, $i++) {
            $name = $first[$i % count($first)] . ' ' . $last[($i * 3) % count($last)];
            $gender = in_array($first[$i % count($first)], ['Bunga', 'Citra', 'Eka', 'Gita', 'Indah', 'Kartika', 'Maya', 'Nanda', 'Putri', 'Sari', 'Umi', 'Vina', 'Zahra'], true) ? 'P' : 'L';
            $uid = make_user('siswa' . ($i + 1), $name, 'siswa', $pw);
            // Orang tua: ortu1 punya dua anak (siswa1 di VII-A & siswa9 di VII-B)
            if ($i === 8) {
                $parentId = db_value("SELECT id FROM users WHERE username = 'ortu1'");
            } else {
                $parentCount++;
                $parentId = make_user('ortu' . $parentCount, $parentFirst[$parentCount % count($parentFirst)] . ' ' . explode(' ', $name)[1], 'orang_tua', $pw,
                    ['phone' => sprintf('08120000%04d', $parentCount)]);
            }
            $studentIds[$classId][] = db_insert('students', [
                'user_id' => $uid, 'nis' => sprintf('2026%04d', $i + 1), 'class_id' => $classId, 'parent_id' => $parentId,
                'gender' => $gender, 'birth_place' => ['Jakarta', 'Bandung', 'Bogor', 'Depok', 'Bekasi'][$i % 5],
                'birth_date' => sprintf('%d-%02d-%02d', 2013 - (int) (substr($cname, 0, 4) === 'VIII'), ($i % 12) + 1, ($i % 27) + 1),
                'address' => 'Jl. Melati No. ' . ($i + 10) . ', Jakarta',
            ]);
        }
    }

    // Absensi 20 hari sekolah terakhir
    $day = new DateTime('today');
    $filled = 0;
    while ($filled < 20) {
        if ((int) $day->format('N') <= 6) {
            foreach ($cid as $classId) {
                foreach ($studentIds[$classId] as $sid) {
                    $r = mt_rand(1, 100);
                    $status = $r <= 90 ? 'H' : ($r <= 94 ? 'S' : ($r <= 97 ? 'I' : 'A'));
                    db_insert('attendance', ['student_id' => $sid, 'att_date' => $day->format('Y-m-d'), 'status' => $status,
                        'note' => $status === 'S' ? 'Demam' : ($status === 'I' ? 'Acara keluarga' : null)]);
                }
            }
            $filled++;
        }
        $day->modify('-1 day');
    }

    // Nilai semester aktif
    foreach ($cid as $cname => $classId) {
        foreach ($assign[$cname] as $aid) {
            foreach ($studentIds[$classId] as $sid) {
                $base = mt_rand(62, 92);
                db_insert('grades', [
                    'student_id' => $sid, 'assignment_id' => $aid, 'academic_year' => $year, 'semester' => $semester,
                    'task_score' => min(100, $base + mt_rand(-5, 8)), 'mid_score' => min(100, $base + mt_rand(-8, 6)),
                    'final_score' => mt_rand(0, 3) ? min(100, $base + mt_rand(-6, 7)) : null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // Catatan rapor
    foreach ($studentIds as $ids) {
        foreach (array_slice($ids, 0, 4) as $sid) {
            db_insert('report_notes', ['student_id' => $sid, 'academic_year' => $year, 'semester' => $semester,
                'note' => 'Ananda menunjukkan semangat belajar yang baik. Tingkatkan terus kedisiplinan dan keaktifan di kelas.']);
        }
    }

    // Tagihan SPP 3 bulan terakhir + pembayaran
    $methods = PAYMENT_METHODS;
    for ($m = 2; $m >= 0; $m--) {
        $monthTs = strtotime(date('Y-m-01') . " -$m month");
        $title = 'SPP ' . MONTHS[(int) date('n', $monthTs)] . ' ' . date('Y', $monthTs);
        foreach ($studentIds as $ids) {
            foreach ($ids as $sid) {
                $inv = db_insert('invoices', ['student_id' => $sid, 'title' => $title, 'amount' => 350000,
                    'due_date' => date('Y-m-10', $monthTs), 'created_by' => $tu, 'created_at' => date('Y-m-d H:i:s', $monthTs)]);
                $r = mt_rand(1, 100);
                if ($m > 0 ? $r <= 85 : $r <= 45) {
                    db_insert('payments', ['invoice_id' => $inv, 'amount' => $r % 7 === 0 ? 200000 : 350000,
                        'paid_at' => date('Y-m-d', min(time(), $monthTs + mt_rand(0, 12) * 86400)),
                        'method' => $methods[mt_rand(0, count($methods) - 1)], 'received_by' => $tu, 'created_at' => date('Y-m-d H:i:s')]);
                }
            }
        }
    }
    foreach ($studentIds[$cid['VIII-A']] as $sid) {
        db_insert('invoices', ['student_id' => $sid, 'title' => 'Study Tour Kelas VIII', 'amount' => 750000,
            'due_date' => date('Y-m-d', strtotime('+30 days')), 'created_by' => $tu, 'created_at' => date('Y-m-d H:i:s')]);
    }

    $ann = [
        [$kepsek, 'semua', 'Selamat Datang Tahun Ajaran ' . $year, "Selamat datang kembali seluruh warga sekolah.\nMari kita jadikan tahun ajaran ini penuh prestasi dan semangat belajar."],
        [$admin, 'guru_semua', 'Batas Input Nilai UTS', "Kepada Bapak/Ibu guru, mohon menyelesaikan input nilai UTS paling lambat akhir bulan ini melalui menu Input Nilai."],
        [$tu, 'orang_tua', 'Informasi Pembayaran SPP', "Pembayaran SPP paling lambat tanggal 10 setiap bulan. Pembayaran dapat dilakukan tunai di ruang TU atau transfer ke rekening sekolah."],
        [$admin, 'siswa', 'Class Meeting', "Class meeting akan dilaksanakan setelah ujian akhir semester. Persiapkan tim terbaik kelasmu!"],
    ];
    foreach ($ann as $k => [$by, $aud, $title, $body]) {
        db_insert('announcements', ['title' => $title, 'body' => $body, 'audience' => $aud, 'created_by' => $by,
            'created_at' => date('Y-m-d H:i:s', strtotime('-' . ($k * 3) . ' days'))]);
    }

    $letters = [
        ['masuk', '421/1234/Disdik/2026', '-12 days', 'Dinas Pendidikan Provinsi', 'Undangan Rapat Kepala Sekolah', 'Disposisi ke Kepala Sekolah'],
        ['masuk', '005/PGRI/IX/2026', '-6 days', 'PGRI Cabang', 'Pemberitahuan Seminar Guru', null],
        ['keluar', '421.3/045/SMPN1/2026', '-4 days', 'Orang Tua Siswa Kelas VIII', 'Pemberitahuan Study Tour', null],
        ['keluar', '421.3/046/SMPN1/2026', '-1 days', 'Puskesmas Kecamatan', 'Permohonan Pemeriksaan Kesehatan Siswa', null],
    ];
    foreach ($letters as [$dir, $no, $rel, $party, $subject, $note]) {
        db_insert('letters', ['direction' => $dir, 'letter_number' => $no, 'letter_date' => date('Y-m-d', strtotime($rel)),
            'party' => $party, 'subject' => $subject, 'note' => $note, 'created_by' => $tu, 'created_at' => date('Y-m-d H:i:s')]);
    }
});

echo "✓ Data demo dibuat. Semua akun demo memakai password: $pw\n";
echo "  admin (Administrasi) · kepsek (Kepala Sekolah) · tu (Tata Usaha)\n";
echo "  guru1..guru4 (Guru) · walikelas1..walikelas3 (Wali Kelas)\n";
echo "  siswa1..siswa24 (Murid) · ortu1..ortu23 (Orang Tua; ortu1 memiliki 2 anak)\n";

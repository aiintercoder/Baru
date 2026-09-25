<?php
declare(strict_types=1);

/**
 * Aturan akses domain: siapa boleh melihat/mengubah data siswa & kelas.
 */

function student_by_user(int $userId): ?array
{
    return db_one(
        'SELECT s.*, u.name, c.name AS class_name FROM students s
         JOIN users u ON u.id = s.user_id LEFT JOIN classes c ON c.id = s.class_id
         WHERE s.user_id = ?',
        [$userId]
    );
}

function student_by_id(int $id): ?array
{
    return db_one(
        'SELECT s.*, u.name, u.email, u.phone, c.name AS class_name, c.homeroom_teacher_id,
                p.name AS parent_name, p.phone AS parent_phone, h.name AS homeroom_name, h.nip AS homeroom_nip
         FROM students s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN classes c ON c.id = s.class_id
         LEFT JOIN users p ON p.id = s.parent_id
         LEFT JOIN users h ON h.id = c.homeroom_teacher_id
         WHERE s.id = ?',
        [$id]
    );
}

function parent_children(int $parentId): array
{
    return db_all(
        'SELECT s.id, s.nis, u.name, c.name AS class_name FROM students s
         JOIN users u ON u.id = s.user_id LEFT JOIN classes c ON c.id = s.class_id
         WHERE s.parent_id = ? ORDER BY u.name',
        [$parentId]
    );
}

function homeroom_class(int $teacherId): ?array
{
    return db_one('SELECT * FROM classes WHERE homeroom_teacher_id = ? ORDER BY name LIMIT 1', [$teacherId]);
}

/** Kelas yang diajar guru (termasuk kelas perwalian). */
function teacher_classes(int $teacherId): array
{
    return db_all(
        'SELECT DISTINCT c.id, c.name, c.level FROM classes c
         WHERE c.homeroom_teacher_id = ?
            OR c.id IN (SELECT class_id FROM teaching_assignments WHERE teacher_id = ?)
         ORDER BY c.level, c.name',
        [$teacherId, $teacherId]
    );
}

function teacher_can_access_class(int $teacherId, int $classId): bool
{
    foreach (teacher_classes($teacherId) as $c) {
        if ((int) $c['id'] === $classId) {
            return true;
        }
    }
    return false;
}

/** Boleh melihat data akademik (rapor, absensi, tagihan) siswa ini? */
function can_view_student(array $user, array $student): bool
{
    return match ($user['role']) {
        'admin', 'kepala_sekolah', 'tata_usaha' => true,
        'siswa'      => (int) $student['user_id'] === (int) $user['id'],
        'orang_tua'  => (int) $student['parent_id'] === (int) $user['id'],
        // Guru/wali kelas hanya dapat membuka rapor & data siswa di kelas perwaliannya
        'wali_kelas' => isset($student['homeroom_teacher_id'])
            && (int) $student['homeroom_teacher_id'] === (int) $user['id'],
        default => false,
    };
}

/**
 * Menentukan siswa yang sedang dilihat oleh siswa sendiri / orang tua / staf.
 * Orang tua bisa memilih anak via ?student_id=.
 */
function resolve_viewed_student(array $user): array
{
    if ($user['role'] === 'siswa') {
        $s = student_by_user((int) $user['id']);
        if (!$s) {
            abort(404, 'Data siswa untuk akun ini belum dilengkapi oleh administrasi.');
        }
        return student_by_id((int) $s['id']);
    }

    $sid = param_int('student_id');
    if ($user['role'] === 'orang_tua') {
        $sid = $sid ?: (int) ($_SESSION['child_id'] ?? 0);
        $children = parent_children((int) $user['id']);
        if (!$children) {
            abort(404, 'Belum ada data anak yang terhubung dengan akun Anda. Hubungi bagian administrasi.');
        }
        if (!in_array($sid, array_map(fn($c) => (int) $c['id'], $children), true)) {
            $sid = (int) $children[0]['id'];
        }
        $_SESSION['child_id'] = $sid;
    }

    $s = $sid ? student_by_id($sid) : null;
    if (!$s) {
        abort(404, 'Data siswa tidak ditemukan.');
    }
    if (!can_view_student($user, $s)) {
        abort(403, 'Anda tidak memiliki akses ke data siswa ini.');
    }
    return $s;
}

function class_students(int $classId): array
{
    return db_all(
        'SELECT s.id, s.nis, s.gender, s.parent_id, u.name, u.phone, p.name AS parent_name, p.phone AS parent_phone
         FROM students s JOIN users u ON u.id = s.user_id
         LEFT JOIN users p ON p.id = s.parent_id
         WHERE s.class_id = ? AND u.active = 1 ORDER BY u.name',
        [$classId]
    );
}

function invoice_summary_for_student(int $studentId): array
{
    return db_all(
        'SELECT i.*, COALESCE((SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id), 0) AS paid
         FROM invoices i WHERE i.student_id = ? ORDER BY i.due_date DESC, i.id DESC',
        [$studentId]
    );
}

function attendance_counts(int $studentId, ?string $from = null, ?string $to = null): array
{
    $sql = 'SELECT status, COUNT(*) AS n FROM attendance WHERE student_id = ?';
    $params = [$studentId];
    if ($from && $to) {
        $sql .= ' AND att_date BETWEEN ? AND ?';
        array_push($params, $from, $to);
    }
    $counts = array_fill_keys(array_keys(ATTENDANCE_STATUS), 0);
    foreach (db_all($sql . ' GROUP BY status', $params) as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    return $counts;
}

/** Nilai siswa untuk periode tertentu, satu baris per mapel di kelasnya. */
function student_grades(array $student, string $year, string $semester): array
{
    $rows = db_all(
        'SELECT sub.name AS subject, sub.code, sub.kkm, t.name AS teacher,
                g.task_score, g.mid_score, g.final_score
         FROM teaching_assignments ta
         JOIN subjects sub ON sub.id = ta.subject_id
         JOIN users t ON t.id = ta.teacher_id
         LEFT JOIN grades g ON g.assignment_id = ta.id AND g.student_id = ?
              AND g.academic_year = ? AND g.semester = ?
         WHERE ta.class_id = ?
         ORDER BY sub.code',
        [$student['id'], $year, $semester, (int) $student['class_id']]
    );
    foreach ($rows as &$r) {
        $r['score'] = score_final($r['task_score'], $r['mid_score'], $r['final_score']);
    }
    return $rows;
}

/** Rentang tanggal semester aktif (Ganjil: Jul–Des, Genap: Jan–Jun). */
function semester_range(string $year, string $semester): array
{
    [$y1, $y2] = array_map('intval', explode('/', $year) + [1 => 0]);
    if (!$y1) {
        $y1 = (int) date('Y');
        $y2 = $y1 + 1;
    }
    return $semester === 'Genap'
        ? [sprintf('%04d-01-01', $y2), sprintf('%04d-06-30', $y2)]
        : [sprintf('%04d-07-01', $y1), sprintf('%04d-12-31', $y1)];
}

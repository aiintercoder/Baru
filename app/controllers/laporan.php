<?php
declare(strict_types=1);

function report_date_range(): array
{
    $p = period();
    [$semFrom, $semTo] = semester_range($p['year'], $p['semester']);
    $from = param('from', $semFrom);
    $to = param('to', min($semTo, date('Y-m-d')));
    if (!valid_date($from) || !valid_date($to) || $from > $to) {
        [$from, $to] = [$semFrom, $semTo];
    }
    return [$from, $to];
}

function report_attendance(): void
{
    [$from, $to] = report_date_range();
    $rows = db_all(
        'SELECT c.id, c.name, a.status, COUNT(*) AS n FROM attendance a
         JOIN students s ON s.id = a.student_id JOIN classes c ON c.id = s.class_id
         WHERE a.att_date BETWEEN ? AND ? GROUP BY c.id, c.name, a.status',
        [$from, $to]
    );
    $classes = [];
    foreach (db_all('SELECT id, name FROM classes ORDER BY level, name') as $c) {
        $classes[$c['id']] = ['name' => $c['name']] + array_fill_keys(array_keys(ATTENDANCE_STATUS), 0);
    }
    foreach ($rows as $r) {
        $classes[$r['id']][$r['status']] = (int) $r['n'];
    }
    // Siswa dengan alpa terbanyak
    $absentees = db_all(
        "SELECT u.name, s.nis, c.name AS class_name, COUNT(*) AS n FROM attendance a
         JOIN students s ON s.id = a.student_id JOIN users u ON u.id = s.user_id LEFT JOIN classes c ON c.id = s.class_id
         WHERE a.status = 'A' AND a.att_date BETWEEN ? AND ?
         GROUP BY s.id, u.name, s.nis, c.name ORDER BY n DESC LIMIT 10",
        [$from, $to]
    );
    render('laporan/attendance', compact('classes', 'absentees', 'from', 'to'), 'Laporan Absensi');
}

function report_grades(): void
{
    $p = period();
    $rows = db_all(
        'SELECT c.id AS class_id, c.name AS class_name, s.id AS subject_id, s.code, s.kkm,
                g.task_score, g.mid_score, g.final_score
         FROM grades g JOIN teaching_assignments ta ON ta.id = g.assignment_id
         JOIN classes c ON c.id = ta.class_id JOIN subjects s ON s.id = ta.subject_id
         WHERE g.academic_year = ? AND g.semester = ?',
        [$p['year'], $p['semester']]
    );
    $subjects = db_all('SELECT id, code, name FROM subjects ORDER BY code');
    $acc = [];
    foreach ($rows as $r) {
        $score = score_final($r['task_score'], $r['mid_score'], $r['final_score']);
        if ($score === null) {
            continue;
        }
        $cell = &$acc[$r['class_id']]['subjects'][$r['subject_id']];
        $cell['sum'] = ($cell['sum'] ?? 0) + $score;
        $cell['n'] = ($cell['n'] ?? 0) + 1;
        $cell['below'] = ($cell['below'] ?? 0) + ($score < $r['kkm'] ? 1 : 0);
        $acc[$r['class_id']]['name'] = $r['class_name'];
        unset($cell);
    }
    $classes = db_all('SELECT id, name FROM classes ORDER BY level, name');
    render('laporan/grades', compact('acc', 'subjects', 'classes') + ['period' => $p], 'Laporan Nilai');
}

function report_finance(): void
{
    [$from, $to] = report_date_range();
    $perClass = db_all(
        'SELECT c.name AS class_name, COUNT(i.id) AS invoice_count, COALESCE(SUM(i.amount), 0) AS billed,
                COALESCE(SUM(pp.paid), 0) AS paid
         FROM invoices i JOIN students s ON s.id = i.student_id LEFT JOIN classes c ON c.id = s.class_id
         LEFT JOIN (SELECT invoice_id, SUM(amount) AS paid FROM payments GROUP BY invoice_id) pp ON pp.invoice_id = i.id
         GROUP BY c.id, c.name ORDER BY c.name'
    );
    $byMethod = db_all(
        'SELECT method, COUNT(*) AS n, SUM(amount) AS total FROM payments
         WHERE paid_at BETWEEN ? AND ? GROUP BY method ORDER BY total DESC',
        [$from, $to]
    );
    $payments = db_all(
        'SELECT p.*, i.title, u.name AS student_name, c.name AS class_name FROM payments p
         JOIN invoices i ON i.id = p.invoice_id JOIN students s ON s.id = i.student_id
         JOIN users u ON u.id = s.user_id LEFT JOIN classes c ON c.id = s.class_id
         WHERE p.paid_at BETWEEN ? AND ? ORDER BY p.paid_at DESC, p.id DESC',
        [$from, $to]
    );
    render('laporan/finance', compact('perClass', 'byMethod', 'payments', 'from', 'to'), 'Laporan Keuangan');
}

<?php
declare(strict_types=1);

function require_homeroom(): array
{
    $class = homeroom_class((int) current_user()['id']);
    if (!$class) {
        abort(404, 'Anda belum ditetapkan sebagai wali kelas. Hubungi bagian administrasi.');
    }
    return $class;
}

function homeroom_students(): void
{
    $class = require_homeroom();
    $p = period();
    [$from, $to] = semester_range($p['year'], $p['semester']);
    $students = class_students((int) $class['id']);
    foreach ($students as &$s) {
        $s['att'] = attendance_counts((int) $s['id'], $from, $to);
        $inv = invoice_summary_for_student((int) $s['id']);
        $s['outstanding'] = array_sum(array_map(fn($i) => max(0, (float) $i['amount'] - (float) $i['paid']), $inv));
    }
    unset($s);
    render('walikelas/students', compact('class', 'students'), 'Siswa Kelas ' . $class['name']);
}

function homeroom_attendance(): void
{
    $class = require_homeroom();
    $month = param('month', date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }
    $from = $month . '-01';
    $to = date('Y-m-t', strtotime($from));
    $students = class_students((int) $class['id']);
    $rows = db_all(
        'SELECT a.student_id, a.att_date, a.status FROM attendance a JOIN students s ON s.id = a.student_id
         WHERE s.class_id = ? AND a.att_date BETWEEN ? AND ?',
        [$class['id'], $from, $to]
    );
    $matrix = [];
    foreach ($rows as $r) {
        $matrix[$r['student_id']][(int) substr($r['att_date'], 8, 2)] = $r['status'];
    }
    $days = (int) date('t', strtotime($from));
    render('walikelas/attendance', compact('class', 'month', 'students', 'matrix', 'days'), 'Rekap Absensi');
}

function homeroom_grades(): void
{
    $class = require_homeroom();
    $p = period();
    $subjects = db_all(
        'SELECT ta.id, s.code, s.name, s.kkm FROM teaching_assignments ta JOIN subjects s ON s.id = ta.subject_id
         WHERE ta.class_id = ? ORDER BY s.code',
        [$class['id']]
    );
    $students = class_students((int) $class['id']);
    $rows = db_all(
        'SELECT g.* FROM grades g JOIN teaching_assignments ta ON ta.id = g.assignment_id
         WHERE ta.class_id = ? AND g.academic_year = ? AND g.semester = ?',
        [$class['id'], $p['year'], $p['semester']]
    );
    $matrix = [];
    foreach ($rows as $r) {
        $matrix[$r['student_id']][$r['assignment_id']] = score_final($r['task_score'], $r['mid_score'], $r['final_score']);
    }
    // Rata-rata & peringkat
    $averages = [];
    foreach ($students as $s) {
        $vals = array_filter($matrix[$s['id']] ?? [], fn($v) => $v !== null);
        $averages[$s['id']] = $vals ? round(array_sum($vals) / count($vals), 2) : null;
    }
    $ranked = array_filter($averages, fn($v) => $v !== null);
    arsort($ranked);
    $ranks = [];
    $i = 0;
    foreach ($ranked as $sid => $_) {
        $ranks[$sid] = ++$i;
    }
    render('walikelas/grades', compact('class', 'subjects', 'students', 'matrix', 'averages', 'ranks') + ['period' => $p], 'Rekap Nilai');
}

function homeroom_notes(): void
{
    $class = require_homeroom();
    $p = period();
    $students = class_students((int) $class['id']);

    if (is_post()) {
        $notes = input_array('note');
        db_transaction(function () use ($students, $notes, $p) {
            foreach ($students as $s) {
                if (!isset($notes[$s['id']]) || !is_string($notes[$s['id']])) {
                    continue;
                }
                db_upsert('report_notes', [
                    'student_id' => $s['id'], 'academic_year' => $p['year'], 'semester' => $p['semester'],
                ], ['note' => nullable(mb_substr(trim($notes[$s['id']]), 0, 2000)), 'updated_by' => current_user()['id']]);
            }
        });
        flash('success', 'Catatan wali kelas disimpan.');
        redirect('walikelas/catatan');
    }

    $existing = [];
    foreach (db_all(
        'SELECT rn.* FROM report_notes rn JOIN students s ON s.id = rn.student_id
         WHERE s.class_id = ? AND rn.academic_year = ? AND rn.semester = ?',
        [$class['id'], $p['year'], $p['semester']]
    ) as $r) {
        $existing[$r['student_id']] = $r['note'];
    }
    render('walikelas/notes', compact('class', 'students', 'existing') + ['period' => $p], 'Catatan Rapor');
}

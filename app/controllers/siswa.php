<?php
declare(strict_types=1);

function student_schedule(): void
{
    $student = resolve_viewed_student(current_user());
    $schedules = $student['class_id'] ? db_all(
        'SELECT sc.*, s.name AS subject, u.name AS teacher FROM schedules sc
         JOIN teaching_assignments ta ON ta.id = sc.assignment_id
         JOIN subjects s ON s.id = ta.subject_id JOIN users u ON u.id = ta.teacher_id
         WHERE ta.class_id = ? ORDER BY sc.day, sc.start_time',
        [$student['class_id']]
    ) : [];
    render('siswa/schedule', compact('student', 'schedules'), 'Jadwal Pelajaran');
}

function student_attendance(): void
{
    $student = resolve_viewed_student(current_user());
    $p = period();
    [$from, $to] = semester_range($p['year'], $p['semester']);
    $records = db_all(
        'SELECT att_date, status, note FROM attendance WHERE student_id = ? ORDER BY att_date DESC LIMIT 200',
        [$student['id']]
    );
    render('siswa/attendance', [
        'student' => $student,
        'records' => $records,
        'semesterCounts' => attendance_counts((int) $student['id'], $from, $to),
        'period' => $p,
    ], 'Kehadiran');
}

function student_invoices(): void
{
    $student = resolve_viewed_student(current_user());
    $invoices = invoice_summary_for_student((int) $student['id']);
    $payments = db_all(
        'SELECT p.*, i.title FROM payments p JOIN invoices i ON i.id = p.invoice_id
         WHERE i.student_id = ? ORDER BY p.paid_at DESC, p.id DESC',
        [$student['id']]
    );
    render('siswa/invoices', compact('student', 'invoices', 'payments'), 'Tagihan Sekolah');
}

function student_report(): void
{
    $me = current_user();
    if ($me['role'] === 'wali_kelas' && param_int('student_id') === 0) {
        redirect('walikelas/kelas');
    }
    if (in_array($me['role'], ['admin', 'kepala_sekolah'], true) && param_int('student_id') === 0) {
        redirect('data/siswa');
    }
    $student = resolve_viewed_student($me);
    $p = period();
    $year = param('year', $p['year']);
    $semester = param('semester', $p['semester']);
    if (!preg_match('/^\d{4}\/\d{4}$/', $year) || !in_array($semester, ['Ganjil', 'Genap'], true)) {
        [$year, $semester] = [$p['year'], $p['semester']];
    }
    [$from, $to] = semester_range($year, $semester);

    $periods = db_all(
        'SELECT DISTINCT academic_year, semester FROM grades WHERE student_id = ? ORDER BY academic_year DESC, semester',
        [$student['id']]
    );
    render('siswa/report', [
        'student'    => $student,
        'grades'     => student_grades($student, $year, $semester),
        'attendance' => attendance_counts((int) $student['id'], $from, $to),
        'note'       => db_value('SELECT note FROM report_notes WHERE student_id = ? AND academic_year = ? AND semester = ?',
            [$student['id'], $year, $semester]),
        'year'       => $year,
        'semester'   => $semester,
        'periods'    => $periods,
    ], 'Rapor ' . $student['name']);
}

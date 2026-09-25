<?php
declare(strict_types=1);

function teacher_schedule(): void
{
    $me = current_user();
    $schedules = db_all(
        'SELECT sc.*, s.name AS subject, c.name AS class_name FROM schedules sc
         JOIN teaching_assignments ta ON ta.id = sc.assignment_id
         JOIN subjects s ON s.id = ta.subject_id JOIN classes c ON c.id = ta.class_id
         WHERE ta.teacher_id = ? ORDER BY sc.day, sc.start_time',
        [$me['id']]
    );
    $assignments = teacher_assignments((int) $me['id']);
    render('guru/schedule', compact('schedules', 'assignments'), 'Jadwal Mengajar');
}

function teacher_assignments(int $teacherId): array
{
    return db_all(
        'SELECT ta.id, ta.class_id, c.name AS class_name, s.name AS subject, s.code, s.kkm,
                (SELECT COUNT(*) FROM students st WHERE st.class_id = ta.class_id) AS student_count
         FROM teaching_assignments ta JOIN classes c ON c.id = ta.class_id JOIN subjects s ON s.id = ta.subject_id
         WHERE ta.teacher_id = ? ORDER BY c.level, c.name, s.code',
        [$teacherId]
    );
}

function teacher_attendance(): void
{
    $me = current_user();
    $classes = teacher_classes((int) $me['id']);
    $default = homeroom_class((int) $me['id'])['id'] ?? ($classes[0]['id'] ?? 0);
    $classId = param_int('class_id', (int) $default);
    $date = param('date', date('Y-m-d'));
    if (!valid_date($date)) {
        $date = date('Y-m-d');
    }
    if ($classId && !teacher_can_access_class((int) $me['id'], $classId)) {
        abort(403, 'Anda tidak mengajar di kelas ini.');
    }

    if (is_post() && $classId) {
        $students = class_students($classId);
        $statuses = input_array('status');
        $notes = input_array('note');
        if ($date > date('Y-m-d')) {
            flash('danger', 'Tidak dapat mengisi absensi untuk tanggal yang akan datang.');
            redirect('guru/absensi', ['class_id' => $classId, 'date' => $date]);
        }
        db_transaction(function () use ($students, $statuses, $notes, $date, $me) {
            foreach ($students as $s) {
                $status = $statuses[$s['id']] ?? '';
                if (!is_string($status) || !isset(ATTENDANCE_STATUS[$status])) {
                    continue;
                }
                $note = is_string($notes[$s['id']] ?? null) ? mb_substr(trim($notes[$s['id']]), 0, 255) : '';
                db_upsert('attendance', ['student_id' => $s['id'], 'att_date' => $date], [
                    'status' => $status, 'note' => nullable($note), 'recorded_by' => $me['id'],
                ]);
            }
        });
        flash('success', 'Absensi tanggal ' . tanggal($date) . ' berhasil disimpan.');
        redirect('guru/absensi', ['class_id' => $classId, 'date' => $date]);
    }

    $students = $classId ? class_students($classId) : [];
    $existing = [];
    if ($students) {
        $rows = db_all(
            'SELECT a.student_id, a.status, a.note FROM attendance a JOIN students s ON s.id = a.student_id
             WHERE s.class_id = ? AND a.att_date = ?',
            [$classId, $date]
        );
        foreach ($rows as $r) {
            $existing[$r['student_id']] = $r;
        }
    }
    render('guru/attendance', compact('classes', 'classId', 'date', 'students', 'existing'), 'Input Absensi');
}

function teacher_grades(): void
{
    $me = current_user();
    $p = period();
    $assignments = teacher_assignments((int) $me['id']);
    $assignmentId = param_int('assignment_id', (int) ($assignments[0]['id'] ?? 0));
    $current = null;
    foreach ($assignments as $a) {
        if ((int) $a['id'] === $assignmentId) {
            $current = $a;
        }
    }
    if ($assignmentId && !$current) {
        abort(403, 'Mata pelajaran ini tidak diampu oleh Anda.');
    }

    if (is_post() && $current) {
        $students = class_students((int) $current['class_id']);
        $scores = input_array('score');
        $invalid = 0;
        db_transaction(function () use ($students, $scores, $current, $p, $me, &$invalid) {
            foreach ($students as $s) {
                $row = $scores[$s['id']] ?? null;
                if (!is_array($row)) {
                    continue;
                }
                $vals = [];
                foreach (['task_score', 'mid_score', 'final_score'] as $k) {
                    $v = is_string($row[$k] ?? null) ? str_replace(',', '.', trim($row[$k])) : '';
                    if ($v === '') {
                        $vals[$k] = null;
                    } elseif (is_numeric($v) && $v >= 0 && $v <= 100) {
                        $vals[$k] = round((float) $v, 2);
                    } else {
                        $vals[$k] = null;
                        $invalid++;
                    }
                }
                db_upsert('grades', [
                    'student_id' => $s['id'], 'assignment_id' => $current['id'],
                    'academic_year' => $p['year'], 'semester' => $p['semester'],
                ], $vals + ['recorded_by' => $me['id'], 'updated_at' => date('Y-m-d H:i:s')]);
            }
        });
        $invalid
            ? flash('warning', "Nilai disimpan, namun $invalid isian tidak valid (harus 0–100) diabaikan.")
            : flash('success', 'Nilai berhasil disimpan.');
        redirect('guru/nilai', ['assignment_id' => $current['id']]);
    }

    $students = $current ? class_students((int) $current['class_id']) : [];
    $grades = [];
    if ($current) {
        $rows = db_all(
            'SELECT * FROM grades WHERE assignment_id = ? AND academic_year = ? AND semester = ?',
            [$current['id'], $p['year'], $p['semester']]
        );
        foreach ($rows as $r) {
            $grades[$r['student_id']] = $r;
        }
    }
    render('guru/grades', compact('assignments', 'current', 'students', 'grades') + ['period' => $p], 'Input Nilai');
}

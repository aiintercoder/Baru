<?php
declare(strict_types=1);

function dashboard_index(): void
{
    $me = current_user();
    $p = period();
    $data = ['me' => $me, 'period' => $p, 'announcements' => visible_announcements($me['role'], 5)];
    $today = date('Y-m-d');
    $dow = (int) date('N');

    switch ($me['role']) {
        case 'admin':
        case 'kepala_sekolah':
            $data['stats'] = [
                'students' => (int) db_value("SELECT COUNT(*) FROM students s JOIN users u ON u.id = s.user_id WHERE u.active = 1"),
                'teachers' => (int) db_value("SELECT COUNT(*) FROM users WHERE role IN ('guru','wali_kelas') AND active = 1"),
                'classes'  => (int) db_value('SELECT COUNT(*) FROM classes'),
                'subjects' => (int) db_value('SELECT COUNT(*) FROM subjects'),
            ];
            $data['todayAttendance'] = db_all(
                'SELECT status, COUNT(*) AS n FROM attendance WHERE att_date = ? GROUP BY status', [$today]
            );
            $data['finance'] = finance_totals();
            $data['roleCounts'] = db_all('SELECT role, COUNT(*) AS n FROM users WHERE active = 1 GROUP BY role');
            break;

        case 'tata_usaha':
            $data['finance'] = finance_totals();
            $data['recentPayments'] = db_all(
                'SELECT p.*, i.title, u.name AS student_name FROM payments p
                 JOIN invoices i ON i.id = p.invoice_id JOIN students s ON s.id = i.student_id
                 JOIN users u ON u.id = s.user_id ORDER BY p.paid_at DESC, p.id DESC LIMIT 6'
            );
            $data['letterCount'] = (int) db_value('SELECT COUNT(*) FROM letters');
            break;

        case 'guru':
        case 'wali_kelas':
            $data['todaySchedule'] = db_all(
                'SELECT sc.*, c.name AS class_name, sub.name AS subject FROM schedules sc
                 JOIN teaching_assignments ta ON ta.id = sc.assignment_id
                 JOIN classes c ON c.id = ta.class_id JOIN subjects sub ON sub.id = ta.subject_id
                 WHERE ta.teacher_id = ? AND sc.day = ? ORDER BY sc.start_time',
                [$me['id'], $dow]
            );
            $data['assignmentCount'] = (int) db_value('SELECT COUNT(*) FROM teaching_assignments WHERE teacher_id = ?', [$me['id']]);
            $data['classCount'] = count(teacher_classes((int) $me['id']));
            if ($me['role'] === 'wali_kelas' && ($hc = homeroom_class((int) $me['id']))) {
                $data['homeroom'] = $hc;
                $data['homeroomCount'] = count(class_students((int) $hc['id']));
                $data['homeroomToday'] = db_all(
                    'SELECT a.status, COUNT(*) AS n FROM attendance a JOIN students s ON s.id = a.student_id
                     WHERE s.class_id = ? AND a.att_date = ? GROUP BY a.status',
                    [$hc['id'], $today]
                );
            }
            break;

        case 'siswa':
        case 'orang_tua':
            $student = resolve_viewed_student($me);
            [$from, $to] = semester_range($p['year'], $p['semester']);
            $grades = student_grades($student, $p['year'], $p['semester']);
            $scored = array_filter(array_column($grades, 'score'), fn($v) => $v !== null);
            $invoices = invoice_summary_for_student((int) $student['id']);
            $data += [
                'student'    => $student,
                'attendance' => attendance_counts((int) $student['id'], $from, $to),
                'average'    => $scored ? round(array_sum($scored) / count($scored), 2) : null,
                'outstanding'=> array_sum(array_map(fn($i) => max(0, (float) $i['amount'] - (float) $i['paid']), $invoices)),
                'todaySchedule' => $student['class_id'] ? db_all(
                    'SELECT sc.*, sub.name AS subject, t.name AS teacher FROM schedules sc
                     JOIN teaching_assignments ta ON ta.id = sc.assignment_id
                     JOIN subjects sub ON sub.id = ta.subject_id JOIN users t ON t.id = ta.teacher_id
                     WHERE ta.class_id = ? AND sc.day = ? ORDER BY sc.start_time',
                    [$student['class_id'], $dow]
                ) : [],
            ];
            break;
    }

    render('dashboard/index', $data, 'Dashboard');
}

function finance_totals(): array
{
    $billed = (float) db_value('SELECT COALESCE(SUM(amount), 0) FROM invoices');
    $paid = (float) db_value('SELECT COALESCE(SUM(amount), 0) FROM payments');
    $monthStart = date('Y-m-01');
    $monthPaid = (float) db_value('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE paid_at >= ?', [$monthStart]);
    return ['billed' => $billed, 'paid' => $paid, 'outstanding' => max(0, $billed - $paid), 'month' => $monthPaid];
}

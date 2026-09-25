<?php
declare(strict_types=1);

function data_students(): void
{
    $classId = param_int('class_id');
    $q = param('q');
    $sql = 'SELECT s.*, u.name, u.active, c.name AS class_name, p.name AS parent_name, p.phone AS parent_phone
            FROM students s JOIN users u ON u.id = s.user_id
            LEFT JOIN classes c ON c.id = s.class_id LEFT JOIN users p ON p.id = s.parent_id WHERE 1 = 1';
    $params = [];
    if ($classId) {
        $sql .= ' AND s.class_id = ?';
        $params[] = $classId;
    }
    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR s.nis LIKE ?)';
        array_push($params, "%$q%", "%$q%");
    }
    render('data/students', [
        'students' => db_all($sql . ' ORDER BY c.level, c.name, u.name', $params),
        'classes'  => db_all('SELECT id, name FROM classes ORDER BY level, name'),
        'classId'  => $classId,
        'q'        => $q,
    ], 'Data Siswa');
}

function data_teachers(): void
{
    $teachers = db_all(
        "SELECT u.*, c.name AS homeroom_class,
                (SELECT COUNT(*) FROM teaching_assignments ta WHERE ta.teacher_id = u.id) AS assignment_count
         FROM users u LEFT JOIN classes c ON c.homeroom_teacher_id = u.id
         WHERE u.role IN ('guru', 'wali_kelas') ORDER BY u.name"
    );
    $subjects = [];
    foreach (db_all(
        'SELECT ta.teacher_id, s.name AS subject, c.name AS class_name FROM teaching_assignments ta
         JOIN subjects s ON s.id = ta.subject_id JOIN classes c ON c.id = ta.class_id ORDER BY c.name'
    ) as $r) {
        $subjects[$r['teacher_id']][] = $r['subject'] . ' (' . $r['class_name'] . ')';
    }
    render('data/teachers', compact('teachers', 'subjects'), 'Data Guru');
}

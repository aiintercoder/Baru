<?php
declare(strict_types=1);

/* ---------- Pengguna ---------- */

function admin_users(): void
{
    $role = param('role');
    $q = param('q');
    $sql = 'SELECT u.*, s.nis, c.name AS class_name FROM users u
            LEFT JOIN students s ON s.user_id = u.id LEFT JOIN classes c ON c.id = s.class_id WHERE 1 = 1';
    $params = [];
    if (isset(ROLES[$role])) {
        $sql .= ' AND u.role = ?';
        $params[] = $role;
    }
    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR u.username LIKE ? OR s.nis LIKE ? OR u.nip LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
    }
    $users = db_all($sql . ' ORDER BY u.role, u.name', $params);
    render('admin/users', compact('users', 'role', 'q'), 'Manajemen Pengguna');
}

function admin_user_form(): void
{
    $id = param_int('id');
    $user = $id ? db_one('SELECT * FROM users WHERE id = ?', [$id]) : null;
    if ($id && !$user) {
        abort(404);
    }
    $student = $user ? db_one('SELECT * FROM students WHERE user_id = ?', [$id]) : null;
    $errors = [];

    if (is_post()) {
        $data = [
            'username' => strtolower(input('username')),
            'name'     => input('name'),
            'role'     => input('role'),
            'nip'      => nullable(input('nip')),
            'email'    => nullable(input('email')),
            'phone'    => nullable(input('phone')),
            'active'   => isset($_POST['active']) ? 1 : 0,
        ];
        $password = (string) ($_POST['password'] ?? '');
        $sdata = [
            'nis'         => input('nis'),
            'class_id'    => nullable(input('class_id')),
            'parent_id'   => nullable(input('parent_id')),
            'gender'      => in_array(input('gender'), ['L', 'P'], true) ? input('gender') : null,
            'birth_place' => nullable(input('birth_place')),
            'birth_date'  => nullable(input('birth_date')),
            'address'     => nullable(input('address')),
        ];

        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $data['username'])) {
            $errors[] = 'Username 3–50 karakter (huruf kecil, angka, titik, garis bawah, strip).';
        } elseif (db_value('SELECT id FROM users WHERE username = ? AND id <> ?', [$data['username'], $id])) {
            $errors[] = 'Username sudah digunakan.';
        }
        if ($data['name'] === '') {
            $errors[] = 'Nama wajib diisi.';
        }
        if (!isset(ROLES[$data['role']])) {
            $errors[] = 'Peran tidak valid.';
        }
        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (!$user && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        } elseif ($user && $password !== '' && strlen($password) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        }
        if ($user && (int) $user['id'] === (int) current_user()['id'] && ($data['role'] !== 'admin' || !$data['active'])) {
            $errors[] = 'Anda tidak dapat menonaktifkan atau mengubah peran akun Anda sendiri.';
        }
        if ($data['role'] === 'siswa') {
            if ($sdata['nis'] === '') {
                $errors[] = 'NIS wajib diisi untuk murid.';
            } elseif (db_value('SELECT id FROM students WHERE nis = ? AND user_id <> ?', [$sdata['nis'], $id])) {
                $errors[] = 'NIS sudah terdaftar.';
            }
            if ($sdata['birth_date'] && !valid_date($sdata['birth_date'])) {
                $errors[] = 'Tanggal lahir tidak valid.';
            }
            if ($sdata['parent_id'] && db_value('SELECT role FROM users WHERE id = ?', [$sdata['parent_id']]) !== 'orang_tua') {
                $errors[] = 'Orang tua yang dipilih tidak valid.';
            }
            if ($sdata['class_id'] && !db_value('SELECT id FROM classes WHERE id = ?', [$sdata['class_id']])) {
                $errors[] = 'Kelas tidak valid.';
            }
        }
        if ($user && $user['role'] === 'siswa' && $data['role'] !== 'siswa') {
            $errors[] = 'Peran murid tidak dapat diubah (data akademik terhubung). Nonaktifkan akun bila perlu.';
        }

        if (!$errors) {
            db_transaction(function () use ($user, $id, $data, $password, $sdata, $student) {
                if ($password !== '') {
                    $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                if ($user) {
                    db_update('users', $data, 'id = ?', [$id]);
                    $uid = $id;
                } else {
                    $uid = db_insert('users', $data + ['created_at' => date('Y-m-d H:i:s')]);
                }
                if ($data['role'] === 'siswa') {
                    $student ? db_update('students', $sdata, 'id = ?', [$student['id']])
                             : db_insert('students', $sdata + ['user_id' => $uid]);
                }
                if ($user && $user['role'] === 'wali_kelas' && $data['role'] !== 'wali_kelas') {
                    db_query('UPDATE classes SET homeroom_teacher_id = NULL WHERE homeroom_teacher_id = ?', [$uid]);
                }
            });
            flash('success', 'Data pengguna berhasil disimpan.');
            redirect('admin/pengguna', ['role' => $data['role']]);
        }
        $user = array_merge($user ?? [], $data);
        $student = array_merge($student ?? [], $sdata);
    }

    render('admin/user_form', [
        'user'    => $user,
        'isNew'   => !$id,
        'student' => $student,
        'errors'  => $errors,
        'classes' => db_all('SELECT id, name FROM classes ORDER BY level, name'),
        'parents' => db_all("SELECT id, name, phone FROM users WHERE role = 'orang_tua' ORDER BY name"),
        'presetRole' => param('role'),
    ], $id ? 'Ubah Pengguna' : 'Tambah Pengguna');
}

function admin_user_delete(): void
{
    $id = (int) input('id');
    if ($id === (int) current_user()['id']) {
        flash('danger', 'Anda tidak dapat menghapus akun sendiri.');
    } elseif (db_value('SELECT COUNT(*) FROM teaching_assignments WHERE teacher_id = ?', [$id])) {
        flash('warning', 'Guru masih memiliki penugasan mengajar. Hapus penugasan terlebih dahulu atau nonaktifkan akun.');
    } else {
        db_query('DELETE FROM users WHERE id = ?', [$id]);
        flash('success', 'Pengguna dihapus.');
    }
    redirect('admin/pengguna');
}

/* ---------- Kelas ---------- */

function admin_classes(): void
{
    $classes = db_all(
        'SELECT c.*, u.name AS homeroom_name,
                (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) AS student_count,
                (SELECT COUNT(*) FROM teaching_assignments ta WHERE ta.class_id = c.id) AS subject_count
         FROM classes c LEFT JOIN users u ON u.id = c.homeroom_teacher_id ORDER BY c.level, c.name'
    );
    render('admin/classes', compact('classes'), 'Data Kelas');
}

function admin_class_form(): void
{
    $id = param_int('id');
    $class = $id ? db_one('SELECT * FROM classes WHERE id = ?', [$id]) : null;
    if ($id && !$class) {
        abort(404);
    }
    if (is_post()) {
        $data = [
            'name'  => input('name'),
            'level' => (int) input('level'),
            'homeroom_teacher_id' => nullable(input('homeroom_teacher_id')),
        ];
        $error = null;
        if ($data['name'] === '' || $data['level'] < 1 || $data['level'] > 12) {
            $error = 'Nama kelas wajib diisi dan tingkat harus 1–12.';
        } elseif (db_value('SELECT id FROM classes WHERE name = ? AND id <> ?', [$data['name'], $id])) {
            $error = 'Nama kelas sudah ada.';
        } elseif ($data['homeroom_teacher_id']) {
            if (db_value('SELECT role FROM users WHERE id = ?', [$data['homeroom_teacher_id']]) !== 'wali_kelas') {
                $error = 'Wali kelas harus pengguna dengan peran Wali Kelas.';
            } elseif (db_value('SELECT name FROM classes WHERE homeroom_teacher_id = ? AND id <> ?', [$data['homeroom_teacher_id'], $id])) {
                $error = 'Guru tersebut sudah menjadi wali kelas di kelas lain.';
            }
        }
        if ($error) {
            flash('danger', $error);
            $class = array_merge($class ?? [], $data);
        } else {
            $class && $id ? db_update('classes', $data, 'id = ?', [$id]) : db_insert('classes', $data);
            flash('success', 'Data kelas disimpan.');
            redirect('admin/kelas');
        }
    }
    $teachers = db_all("SELECT id, name FROM users WHERE role = 'wali_kelas' AND active = 1 ORDER BY name");
    render('admin/class_form', ['class' => $class, 'teachers' => $teachers, 'isNew' => !$id], $id ? 'Ubah Kelas' : 'Tambah Kelas');
}

function admin_class_delete(): void
{
    $id = (int) input('id');
    if (db_value('SELECT COUNT(*) FROM students WHERE class_id = ?', [$id])) {
        flash('warning', 'Kelas masih memiliki siswa. Pindahkan siswa terlebih dahulu.');
    } else {
        db_query('DELETE FROM classes WHERE id = ?', [$id]);
        flash('success', 'Kelas dihapus.');
    }
    redirect('admin/kelas');
}

/* ---------- Mata Pelajaran ---------- */

function admin_subjects(): void
{
    $subjects = db_all(
        'SELECT s.*, (SELECT COUNT(*) FROM teaching_assignments ta WHERE ta.subject_id = s.id) AS used
         FROM subjects s ORDER BY s.code'
    );
    render('admin/subjects', compact('subjects'), 'Mata Pelajaran');
}

function admin_subject_form(): void
{
    $id = param_int('id');
    $subject = $id ? db_one('SELECT * FROM subjects WHERE id = ?', [$id]) : null;
    if ($id && !$subject) {
        abort(404);
    }
    if (is_post()) {
        $data = ['code' => strtoupper(input('code')), 'name' => input('name'), 'kkm' => (int) input('kkm', '75')];
        if ($data['code'] === '' || $data['name'] === '' || $data['kkm'] < 0 || $data['kkm'] > 100) {
            flash('danger', 'Kode dan nama wajib diisi, KKM 0–100.');
            $subject = array_merge($subject ?? [], $data);
        } elseif (db_value('SELECT id FROM subjects WHERE code = ? AND id <> ?', [$data['code'], $id])) {
            flash('danger', 'Kode mata pelajaran sudah digunakan.');
            $subject = array_merge($subject ?? [], $data);
        } else {
            $id ? db_update('subjects', $data, 'id = ?', [$id]) : db_insert('subjects', $data);
            flash('success', 'Mata pelajaran disimpan.');
            redirect('admin/mapel');
        }
    }
    render('admin/subject_form', ['subject' => $subject, 'isNew' => !$id], $id ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran');
}

function admin_subject_delete(): void
{
    $id = (int) input('id');
    if (db_value('SELECT COUNT(*) FROM teaching_assignments WHERE subject_id = ?', [$id])) {
        flash('warning', 'Mata pelajaran masih digunakan pada penugasan guru.');
    } else {
        db_query('DELETE FROM subjects WHERE id = ?', [$id]);
        flash('success', 'Mata pelajaran dihapus.');
    }
    redirect('admin/mapel');
}

/* ---------- Penugasan Guru ---------- */

function admin_assignments(): void
{
    if (is_post()) {
        $teacher = (int) input('teacher_id');
        $class = (int) input('class_id');
        $subject = (int) input('subject_id');
        $validTeacher = in_array(db_value('SELECT role FROM users WHERE id = ?', [$teacher]), TEACHER_ROLES, true);
        if (!$validTeacher || !db_value('SELECT id FROM classes WHERE id = ?', [$class]) || !db_value('SELECT id FROM subjects WHERE id = ?', [$subject])) {
            flash('danger', 'Pilih guru, kelas, dan mata pelajaran yang valid.');
        } elseif ($existing = db_value('SELECT id FROM teaching_assignments WHERE class_id = ? AND subject_id = ?', [$class, $subject])) {
            db_update('teaching_assignments', ['teacher_id' => $teacher], 'id = ?', [$existing]);
            flash('success', 'Guru pengampu mata pelajaran di kelas tersebut diperbarui.');
        } else {
            db_insert('teaching_assignments', ['teacher_id' => $teacher, 'class_id' => $class, 'subject_id' => $subject]);
            flash('success', 'Penugasan ditambahkan.');
        }
        redirect('admin/penugasan', ['class_id' => $class]);
    }

    $classId = param_int('class_id');
    $sql = 'SELECT ta.*, u.name AS teacher, c.name AS class_name, s.name AS subject, s.code
            FROM teaching_assignments ta JOIN users u ON u.id = ta.teacher_id
            JOIN classes c ON c.id = ta.class_id JOIN subjects s ON s.id = ta.subject_id';
    $params = [];
    if ($classId) {
        $sql .= ' WHERE ta.class_id = ?';
        $params[] = $classId;
    }
    render('admin/assignments', [
        'assignments' => db_all($sql . ' ORDER BY c.level, c.name, s.code', $params),
        'teachers'    => db_all("SELECT id, name, role FROM users WHERE role IN ('guru','wali_kelas') AND active = 1 ORDER BY name"),
        'classes'     => db_all('SELECT id, name FROM classes ORDER BY level, name'),
        'subjects'    => db_all('SELECT id, code, name FROM subjects ORDER BY code'),
        'classId'     => $classId,
    ], 'Penugasan Guru');
}

function admin_assignment_delete(): void
{
    db_query('DELETE FROM teaching_assignments WHERE id = ?', [(int) input('id')]);
    flash('success', 'Penugasan dihapus (jadwal & nilai terkait ikut terhapus).');
    back('admin/penugasan');
}

/* ---------- Jadwal ---------- */

function admin_schedules(): void
{
    $classes = db_all('SELECT id, name FROM classes ORDER BY level, name');
    $classId = param_int('class_id', (int) ($classes[0]['id'] ?? 0));

    if (is_post()) {
        $assignment = (int) input('assignment_id');
        $day = (int) input('day');
        $start = input('start_time');
        $end = input('end_time');
        $room = input('room');
        $ta = db_one('SELECT * FROM teaching_assignments WHERE id = ?', [$assignment]);
        $timeOk = preg_match('/^\d{2}:\d{2}$/', $start) && preg_match('/^\d{2}:\d{2}$/', $end) && $start < $end;
        if (!$ta || !isset(DAYS[$day]) || !$timeOk) {
            flash('danger', 'Data jadwal tidak valid. Pastikan jam mulai lebih awal dari jam selesai.');
        } elseif ($conflict = schedule_conflict($ta, $day, $start, $end)) {
            flash('danger', $conflict);
        } else {
            db_insert('schedules', ['assignment_id' => $assignment, 'day' => $day, 'start_time' => $start, 'end_time' => $end, 'room' => nullable($room)]);
            flash('success', 'Jadwal ditambahkan.');
        }
        redirect('admin/jadwal', ['class_id' => $ta['class_id'] ?? $classId]);
    }

    $schedules = $classId ? db_all(
        'SELECT sc.*, s.name AS subject, u.name AS teacher FROM schedules sc
         JOIN teaching_assignments ta ON ta.id = sc.assignment_id
         JOIN subjects s ON s.id = ta.subject_id JOIN users u ON u.id = ta.teacher_id
         WHERE ta.class_id = ? ORDER BY sc.day, sc.start_time',
        [$classId]
    ) : [];
    $assignments = $classId ? db_all(
        'SELECT ta.id, s.name AS subject, u.name AS teacher FROM teaching_assignments ta
         JOIN subjects s ON s.id = ta.subject_id JOIN users u ON u.id = ta.teacher_id
         WHERE ta.class_id = ? ORDER BY s.code',
        [$classId]
    ) : [];
    render('admin/schedules', compact('classes', 'classId', 'schedules', 'assignments'), 'Jadwal Pelajaran');
}

/** Cek bentrok jadwal kelas atau guru pada hari & jam yang sama. */
function schedule_conflict(array $ta, int $day, string $start, string $end): ?string
{
    $row = db_one(
        'SELECT c.name AS class_name, u.name AS teacher, ta.class_id, ta.teacher_id FROM schedules sc
         JOIN teaching_assignments ta ON ta.id = sc.assignment_id
         JOIN classes c ON c.id = ta.class_id JOIN users u ON u.id = ta.teacher_id
         WHERE sc.day = ? AND sc.start_time < ? AND sc.end_time > ? AND (ta.class_id = ? OR ta.teacher_id = ?)
         LIMIT 1',
        [$day, $end, $start, $ta['class_id'], $ta['teacher_id']]
    );
    if (!$row) {
        return null;
    }
    return (int) $row['class_id'] === (int) $ta['class_id']
        ? 'Jadwal bentrok dengan pelajaran lain di kelas ' . $row['class_name'] . '.'
        : 'Guru ' . $row['teacher'] . ' sudah mengajar di kelas ' . $row['class_name'] . ' pada jam tersebut.';
}

function admin_schedule_delete(): void
{
    db_query('DELETE FROM schedules WHERE id = ?', [(int) input('id')]);
    flash('success', 'Jadwal dihapus.');
    back('admin/jadwal');
}

/* ---------- Pengaturan ---------- */

function admin_settings(): void
{
    $keys = ['school_name', 'school_address', 'school_phone', 'principal_name', 'principal_nip', 'academic_year', 'semester'];
    if (is_post()) {
        $year = input('academic_year');
        $sem = input('semester');
        if (!preg_match('/^\d{4}\/\d{4}$/', $year) || !in_array($sem, ['Ganjil', 'Genap'], true)) {
            flash('danger', 'Format tahun ajaran harus YYYY/YYYY dan semester Ganjil/Genap.');
        } else {
            foreach ($keys as $k) {
                set_setting($k, input($k));
            }
            flash('success', 'Pengaturan disimpan.');
        }
        redirect('admin/pengaturan');
    }
    $values = [];
    foreach ($keys as $k) {
        $values[$k] = setting($k);
    }
    render('admin/settings', compact('values'), 'Pengaturan Sekolah');
}

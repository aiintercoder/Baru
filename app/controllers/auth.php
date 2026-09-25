<?php
declare(strict_types=1);

function auth_login(): void
{
    if (current_user()) {
        redirect();
    }
    $error = null;
    $username = '';
    if (is_post()) {
        $username = input('username');
        $error = attempt_login($username, (string) ($_POST['password'] ?? ''));
        if ($error === null) {
            redirect();
        }
    }
    render('auth/login', ['error' => $error, 'username' => $username], 'Masuk');
}

function auth_logout(): void
{
    if (is_post()) {
        logout();
    }
    redirect('login');
}

function auth_profile(): void
{
    $me = current_user();
    if (is_post()) {
        $action = input('action');
        if ($action === 'profile') {
            $email = input('email');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('danger', 'Format email tidak valid.');
            } else {
                db_update('users', ['email' => nullable($email), 'phone' => nullable(input('phone'))], 'id = ?', [$me['id']]);
                flash('success', 'Profil berhasil diperbarui.');
            }
        } elseif ($action === 'password') {
            $old = (string) ($_POST['old_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            if (!password_verify($old, $me['password_hash'])) {
                flash('danger', 'Password lama salah.');
            } elseif (strlen($new) < 8) {
                flash('danger', 'Password baru minimal 8 karakter.');
            } elseif ($new !== $confirm) {
                flash('danger', 'Konfirmasi password tidak cocok.');
            } else {
                db_update('users', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], 'id = ?', [$me['id']]);
                session_regenerate_id(true);
                flash('success', 'Password berhasil diganti.');
            }
        }
        redirect('profil');
    }

    $student = $me['role'] === 'siswa' ? student_by_user((int) $me['id']) : null;
    render('auth/profile', ['me' => $me, 'student' => $student], 'Profil Saya');
}

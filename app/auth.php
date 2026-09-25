<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 60;

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $uid = $_SESSION['uid'] ?? null;
    $user = $uid ? db_one('SELECT * FROM users WHERE id = ? AND active = 1', [$uid]) : null;
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('login');
    }
    return $u;
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
    return $u;
}

/** @return string|null pesan kesalahan, atau null bila berhasil */
function attempt_login(string $username, string $password): ?string
{
    $lock = $_SESSION['login_lock_until'] ?? 0;
    if ($lock > time()) {
        return 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($lock - time()) . ' detik.';
    }

    $u = db_one('SELECT * FROM users WHERE username = ?', [$username]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_lock_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_attempts'] = 0;
        }
        return 'Username atau password salah.';
    }
    if (!(int) $u['active']) {
        return 'Akun Anda dinonaktifkan. Hubungi bagian administrasi.';
    }

    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db_update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
    }

    session_regenerate_id(true);
    unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
    $_SESSION['uid'] = (int) $u['id'];
    return null;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

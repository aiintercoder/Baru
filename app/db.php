<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = config('db');
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if ($c['driver'] === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        } else {
            $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
        }
    } catch (PDOException $e) {
        if (PHP_SAPI === 'cli') {
            throw $e;
        }
        error_log((string) $e);
        // Buang template yang mungkin sedang di-render agar pesan tidak tercampur
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $local = is_local_request();
        // XAMPP/localhost: database belum dibuat -> arahkan ke halaman instalasi
        if ($local && $c['driver'] === 'mysql' && str_contains($e->getMessage(), 'Unknown database')) {
            header('Location: setup.php');
            exit;
        }
        http_response_code(500);
        exit('<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Database tidak terhubung</title>'
            . '<body style="font-family:system-ui,sans-serif;background:#f4f6fb;margin:0;padding:16px">'
            . '<div style="max-width:640px;margin:64px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px">'
            . '<h2 style="margin-top:0;color:#b91c1c">Tidak dapat terhubung ke database</h2>'
            . '<p>Aplikasi tidak bisa tersambung ke server database. Periksa hal berikut:</p><ol style="line-height:1.7">'
            . '<li>Nama database, user, dan password di <code>config.local.php</code> (atau <code>config.php</code>) sudah benar.</li>'
            . '<li>User database sudah ditambahkan ke database dengan hak <em>ALL PRIVILEGES</em>.</li>'
            . '<li>File <code>database/sekolah_mysql_kosong.sql</code> atau <code>sekolah_mysql.sql</code> sudah di-import.</li>'
            . '<li>Server MySQL/MariaDB sedang berjalan (di XAMPP: klik <b>Start</b> pada modul MySQL).</li></ol>'
            . ($local ? '<p><a href="setup.php" style="display:inline-block;background:#2563eb;color:#fff;padding:8px 14px;'
                . 'border-radius:8px;text-decoration:none">Buka halaman instalasi</a></p>' : '')
            . '<p style="color:#64748b;font-size:14px;margin-bottom:0">Detail kesalahan dicatat di log error server.</p></div></body></html>');
    }

    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_value(string $sql, array $params = []): mixed
{
    $v = db_query($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** Nama tabel/kolom hanya berasal dari kode, bukan input pengguna. */
function db_insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', array_fill(0, count($cols), '?'))
    );
    db_query($sql, array_values($data));
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $whereParams = []): int
{
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
    $st = db_query("UPDATE $table SET $set WHERE $where", array_merge(array_values($data), $whereParams));
    return $st->rowCount();
}

/** Insert atau update berdasarkan kunci unik (portabel SQLite & MySQL). */
function db_upsert(string $table, array $keys, array $data): void
{
    $where = implode(' AND ', array_map(fn($c) => "$c = ?", array_keys($keys)));
    $id = db_value("SELECT id FROM $table WHERE $where", array_values($keys));
    if ($id !== null) {
        db_update($table, $data, 'id = ?', [$id]);
    } else {
        db_insert($table, $keys + $data);
    }
}

function db_transaction(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

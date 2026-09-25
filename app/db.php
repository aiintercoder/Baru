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

    if ($c['driver'] === 'mysql') {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
    } else {
        $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
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

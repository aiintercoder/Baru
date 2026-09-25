<?php
/**
 * Konfigurasi aplikasi.
 * Nilai dapat ditimpa melalui environment variable (mis. DB_DRIVER=mysql).
 */
return [
    'app_name' => getenv('APP_NAME') ?: 'SIAKAD Sekolah',
    'debug'    => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Asia/Jakarta',

    'db' => [
        // 'sqlite' (default, tanpa instalasi server) atau 'mysql'
        'driver'      => getenv('DB_DRIVER') ?: 'sqlite',
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: __DIR__ . '/database/sekolah.sqlite',
        'host'        => getenv('DB_HOST') ?: '127.0.0.1',
        'port'        => getenv('DB_PORT') ?: '3306',
        'name'        => getenv('DB_NAME') ?: 'sekolah',
        'user'        => getenv('DB_USER') ?: 'root',
        'pass'        => getenv('DB_PASS') ?: '',
    ],

    // Bobot nilai akhir (persentase)
    'grade_weights' => ['task' => 30, 'mid' => 30, 'final' => 40],
];

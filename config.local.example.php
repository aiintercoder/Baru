<?php
/**
 * Salin file ini menjadi config.local.php lalu isi sesuai server Anda.
 * Nilai di sini menimpa config.php. File config.local.php tidak ikut ter-commit ke git.
 *
 * Contoh untuk cPanel: nama database & user diawali nama akun cPanel, mis. "akunsaya_".
 */
return [
    'app_name' => 'SIAKAD Sekolah',
    'debug'    => false,              // biarkan false di server produksi

    'db' => [
        'driver' => 'mysql',
        'host'   => 'localhost',
        'port'   => '3306',
        'name'   => 'akunsaya_sekolah',
        'user'   => 'akunsaya_siakad',
        'pass'   => 'GANTI_DENGAN_PASSWORD_DATABASE',
    ],
];

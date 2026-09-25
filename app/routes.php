<?php
declare(strict_types=1);

/**
 * route => [file controller, fungsi, peran yang diizinkan]
 * Peran null = publik, [] = semua pengguna yang login.
 */
const STAFF = ['admin', 'kepala_sekolah', 'tata_usaha'];

return [
    'login'   => ['auth', 'auth_login', null],
    'logout'  => ['auth', 'auth_logout', []],
    ''        => ['dashboard', 'dashboard_index', []],
    'profil'  => ['auth', 'auth_profile', []],

    // Pengumuman
    'pengumuman'       => ['pengumuman', 'announcement_index', []],
    'pengumuman/form'  => ['pengumuman', 'announcement_form', ['admin', 'kepala_sekolah', 'tata_usaha']],
    'pengumuman/hapus' => ['pengumuman', 'announcement_delete', ['admin', 'kepala_sekolah', 'tata_usaha']],

    // Administrasi
    'admin/pengguna'       => ['admin', 'admin_users', ['admin']],
    'admin/pengguna/form'  => ['admin', 'admin_user_form', ['admin']],
    'admin/pengguna/hapus' => ['admin', 'admin_user_delete', ['admin']],
    'admin/kelas'          => ['admin', 'admin_classes', ['admin']],
    'admin/kelas/form'     => ['admin', 'admin_class_form', ['admin']],
    'admin/kelas/hapus'    => ['admin', 'admin_class_delete', ['admin']],
    'admin/mapel'          => ['admin', 'admin_subjects', ['admin']],
    'admin/mapel/form'     => ['admin', 'admin_subject_form', ['admin']],
    'admin/mapel/hapus'    => ['admin', 'admin_subject_delete', ['admin']],
    'admin/penugasan'      => ['admin', 'admin_assignments', ['admin']],
    'admin/penugasan/hapus'=> ['admin', 'admin_assignment_delete', ['admin']],
    'admin/jadwal'         => ['admin', 'admin_schedules', ['admin']],
    'admin/jadwal/hapus'   => ['admin', 'admin_schedule_delete', ['admin']],
    'admin/pengaturan'     => ['admin', 'admin_settings', ['admin']],

    // Guru & Wali Kelas
    'guru/jadwal'  => ['guru', 'teacher_schedule', TEACHER_ROLES],
    'guru/absensi' => ['guru', 'teacher_attendance', TEACHER_ROLES],
    'guru/nilai'   => ['guru', 'teacher_grades', TEACHER_ROLES],

    'walikelas/kelas'        => ['walikelas', 'homeroom_students', ['wali_kelas']],
    'walikelas/rekap-absensi'=> ['walikelas', 'homeroom_attendance', ['wali_kelas']],
    'walikelas/rekap-nilai'  => ['walikelas', 'homeroom_grades', ['wali_kelas']],
    'walikelas/catatan'      => ['walikelas', 'homeroom_notes', ['wali_kelas']],

    // Murid & Orang Tua
    'siswa/jadwal'  => ['siswa', 'student_schedule', ['siswa', 'orang_tua']],
    'siswa/absensi' => ['siswa', 'student_attendance', ['siswa', 'orang_tua']],
    'siswa/tagihan' => ['siswa', 'student_invoices', ['siswa', 'orang_tua']],
    'rapor'         => ['siswa', 'student_report', ['siswa', 'orang_tua', 'wali_kelas', 'admin', 'kepala_sekolah']],

    // Tata Usaha
    'keuangan/tagihan'       => ['keuangan', 'finance_invoices', ['tata_usaha']],
    'keuangan/tagihan/form'  => ['keuangan', 'finance_invoice_form', ['tata_usaha']],
    'keuangan/tagihan/hapus' => ['keuangan', 'finance_invoice_delete', ['tata_usaha']],
    'keuangan/bayar'         => ['keuangan', 'finance_payment', ['tata_usaha']],
    'keuangan/bayar/hapus'   => ['keuangan', 'finance_payment_delete', ['tata_usaha']],
    'keuangan/kwitansi'      => ['keuangan', 'finance_receipt', ['tata_usaha', 'siswa', 'orang_tua']],
    'surat'                  => ['surat', 'letter_index', ['tata_usaha', 'kepala_sekolah']],
    'surat/form'             => ['surat', 'letter_form', ['tata_usaha']],
    'surat/hapus'            => ['surat', 'letter_delete', ['tata_usaha']],

    // Data & laporan
    'data/siswa'        => ['data', 'data_students', STAFF],
    'data/guru'         => ['data', 'data_teachers', STAFF],
    'laporan/absensi'   => ['laporan', 'report_attendance', ['kepala_sekolah', 'admin']],
    'laporan/nilai'     => ['laporan', 'report_grades', ['kepala_sekolah', 'admin']],
    'laporan/keuangan'  => ['laporan', 'report_finance', ['kepala_sekolah', 'tata_usaha']],
];

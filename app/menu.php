<?php
declare(strict_types=1);

/** Menu sidebar per peran: [judul bagian => [[label, route, ikon], ...]] */
function menu_for(string $role): array
{
    $common = ['Umum' => [
        ['Dashboard', '', 'speedometer2'],
        ['Pengumuman', 'pengumuman', 'megaphone'],
    ]];

    $teaching = ['Mengajar' => [
        ['Jadwal Mengajar', 'guru/jadwal', 'calendar-week'],
        ['Input Absensi', 'guru/absensi', 'clipboard-check'],
        ['Input Nilai', 'guru/nilai', 'pencil-square'],
    ]];

    $menus = [
        'admin' => [
            'Master Data' => [
                ['Pengguna', 'admin/pengguna', 'people'],
                ['Kelas', 'admin/kelas', 'door-open'],
                ['Mata Pelajaran', 'admin/mapel', 'book'],
                ['Penugasan Guru', 'admin/penugasan', 'person-workspace'],
                ['Jadwal Pelajaran', 'admin/jadwal', 'calendar3'],
            ],
            'Laporan' => [
                ['Data Siswa', 'data/siswa', 'mortarboard'],
                ['Data Guru', 'data/guru', 'person-badge'],
                ['Laporan Absensi', 'laporan/absensi', 'bar-chart'],
                ['Laporan Nilai', 'laporan/nilai', 'graph-up'],
            ],
            'Sistem' => [
                ['Pengaturan', 'admin/pengaturan', 'gear'],
            ],
        ],
        'kepala_sekolah' => [
            'Monitoring' => [
                ['Data Siswa', 'data/siswa', 'mortarboard'],
                ['Data Guru', 'data/guru', 'person-badge'],
                ['Laporan Absensi', 'laporan/absensi', 'bar-chart'],
                ['Laporan Nilai', 'laporan/nilai', 'graph-up'],
                ['Laporan Keuangan', 'laporan/keuangan', 'cash-stack'],
                ['Arsip Surat', 'surat', 'envelope-paper'],
            ],
        ],
        'tata_usaha' => [
            'Keuangan' => [
                ['Tagihan & Pembayaran', 'keuangan/tagihan', 'receipt'],
                ['Laporan Keuangan', 'laporan/keuangan', 'cash-stack'],
            ],
            'Administrasi' => [
                ['Surat Masuk/Keluar', 'surat', 'envelope-paper'],
                ['Data Siswa', 'data/siswa', 'mortarboard'],
                ['Data Guru', 'data/guru', 'person-badge'],
            ],
        ],
        'guru' => $teaching,
        'wali_kelas' => $teaching + ['Perwalian' => [
            ['Siswa Kelas Saya', 'walikelas/kelas', 'people'],
            ['Rekap Absensi', 'walikelas/rekap-absensi', 'calendar-check'],
            ['Rekap Nilai', 'walikelas/rekap-nilai', 'table'],
            ['Catatan Rapor', 'walikelas/catatan', 'journal-text'],
        ]],
        'siswa' => ['Akademik' => [
            ['Jadwal Pelajaran', 'siswa/jadwal', 'calendar-week'],
            ['Nilai & Rapor', 'rapor', 'award'],
            ['Kehadiran', 'siswa/absensi', 'calendar-check'],
            ['Tagihan Sekolah', 'siswa/tagihan', 'receipt'],
        ]],
        'orang_tua' => ['Anak Saya' => [
            ['Jadwal Pelajaran', 'siswa/jadwal', 'calendar-week'],
            ['Nilai & Rapor', 'rapor', 'award'],
            ['Kehadiran', 'siswa/absensi', 'calendar-check'],
            ['Tagihan Sekolah', 'siswa/tagihan', 'receipt'],
        ]],
    ];

    return $common + ($menus[$role] ?? []) + ['Akun' => [['Profil & Password', 'profil', 'person-circle']]];
}

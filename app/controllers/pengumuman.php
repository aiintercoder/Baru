<?php
declare(strict_types=1);

const AUDIENCES = [
    'semua'          => 'Semua Pengguna',
    'guru_semua'     => 'Semua Guru & Wali Kelas',
    'siswa'          => 'Murid',
    'orang_tua'      => 'Orang Tua Murid',
    'guru'           => 'Guru',
    'wali_kelas'     => 'Wali Kelas',
    'tata_usaha'     => 'Tata Usaha',
    'kepala_sekolah' => 'Kepala Sekolah',
    'admin'          => 'Administrasi',
];

function announcement_index(): void
{
    $me = current_user();
    render('pengumuman/index', [
        'items'   => visible_announcements($me['role']),
        'canEdit' => has_role('admin', 'kepala_sekolah', 'tata_usaha'),
    ], 'Pengumuman');
}

function announcement_form(): void
{
    $me = current_user();
    $id = param_int('id');
    $item = $id ? db_one('SELECT * FROM announcements WHERE id = ?', [$id]) : null;
    if ($id && !$item) {
        abort(404);
    }
    if ($item && !can_edit_announcement($me, $item)) {
        abort(403, 'Anda hanya dapat mengubah pengumuman yang Anda buat.');
    }

    if (is_post()) {
        $data = [
            'title'    => input('title'),
            'body'     => input('body'),
            'audience' => input('audience'),
        ];
        if ($data['title'] === '' || $data['body'] === '' || !isset(AUDIENCES[$data['audience']])) {
            flash('danger', 'Judul, isi, dan sasaran pengumuman wajib diisi.');
            $item = array_merge($item ?? [], $data);
        } else {
            if ($item) {
                db_update('announcements', $data, 'id = ?', [$id]);
            } else {
                db_insert('announcements', $data + ['created_by' => $me['id'], 'created_at' => date('Y-m-d H:i:s')]);
            }
            flash('success', 'Pengumuman berhasil disimpan.');
            redirect('pengumuman');
        }
    }
    render('pengumuman/form', ['item' => $item], $item && $id ? 'Ubah Pengumuman' : 'Buat Pengumuman');
}

function announcement_delete(): void
{
    $item = db_one('SELECT * FROM announcements WHERE id = ?', [(int) input('id')]);
    if ($item && is_post() && can_edit_announcement(current_user(), $item)) {
        db_query('DELETE FROM announcements WHERE id = ?', [$item['id']]);
        flash('success', 'Pengumuman dihapus.');
    }
    redirect('pengumuman');
}

function can_edit_announcement(array $user, array $item): bool
{
    return in_array($user['role'], ['admin', 'kepala_sekolah'], true) || (int) $item['created_by'] === (int) $user['id'];
}

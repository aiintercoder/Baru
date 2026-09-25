<?php
declare(strict_types=1);

function letter_index(): void
{
    $direction = param('direction');
    $q = param('q');
    $sql = 'SELECT l.*, u.name AS author FROM letters l LEFT JOIN users u ON u.id = l.created_by WHERE 1 = 1';
    $params = [];
    if (in_array($direction, ['masuk', 'keluar'], true)) {
        $sql .= ' AND l.direction = ?';
        $params[] = $direction;
    }
    if ($q !== '') {
        $sql .= ' AND (l.letter_number LIKE ? OR l.party LIKE ? OR l.subject LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
    }
    render('surat/index', [
        'letters'   => db_all($sql . ' ORDER BY l.letter_date DESC, l.id DESC', $params),
        'direction' => $direction,
        'q'         => $q,
        'canEdit'   => has_role('tata_usaha'),
    ], 'Arsip Surat');
}

function letter_form(): void
{
    $id = param_int('id');
    $letter = $id ? db_one('SELECT * FROM letters WHERE id = ?', [$id]) : null;
    if ($id && !$letter) {
        abort(404);
    }
    if (is_post()) {
        $data = [
            'direction'     => input('direction'),
            'letter_number' => input('letter_number'),
            'letter_date'   => input('letter_date'),
            'party'         => input('party'),
            'subject'       => input('subject'),
            'note'          => nullable(input('note')),
        ];
        if (!in_array($data['direction'], ['masuk', 'keluar'], true) || $data['letter_number'] === ''
            || !valid_date($data['letter_date']) || $data['party'] === '' || $data['subject'] === '') {
            flash('danger', 'Lengkapi jenis, nomor, tanggal, pengirim/penerima, dan perihal surat.');
            $letter = array_merge($letter ?? [], $data);
        } else {
            if ($id) {
                db_update('letters', $data, 'id = ?', [$id]);
            } else {
                db_insert('letters', $data + ['created_by' => current_user()['id'], 'created_at' => date('Y-m-d H:i:s')]);
            }
            flash('success', 'Data surat disimpan.');
            redirect('surat', ['direction' => $data['direction']]);
        }
    }
    render('surat/form', ['letter' => $letter, 'isNew' => !$id], $id ? 'Ubah Surat' : 'Catat Surat');
}

function letter_delete(): void
{
    db_query('DELETE FROM letters WHERE id = ?', [(int) input('id')]);
    flash('success', 'Data surat dihapus.');
    redirect('surat');
}

<div class="page-header"><h1><i class="bi bi-person-workspace"></i> Penugasan Guru</h1></div>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Tetapkan Guru Pengampu</div>
    <div class="card-body">
        <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <div class="col-md-3"><select class="form-select" name="class_id" required>
                <option value="">Kelas…</option>
                <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $classId) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="col-md-4"><select class="form-select" name="subject_id" required>
                <option value="">Mata pelajaran…</option>
                <?php foreach ($subjects as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['code'] . ' — ' . $s['name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="col-md-3"><select class="form-select" name="teacher_id" required>
                <option value="">Guru…</option>
                <?php foreach ($teachers as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?><?= $t['role'] === 'wali_kelas' ? ' (Wali Kelas)' : '' ?></option><?php endforeach; ?>
            </select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Simpan</button></div>
        </form>
        <div class="form-text">Satu mata pelajaran per kelas diampu oleh satu guru. Menyimpan ulang akan mengganti gurunya.</div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-white">
        <form method="get" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="r" value="admin/penugasan">
            <span class="small text-muted">Filter kelas:</span>
            <select class="form-select form-select-sm w-auto" name="class_id" onchange="this.form.submit()">
                <option value="">Semua</option>
                <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $classId) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Kelas</th><th>Mata Pelajaran</th><th>Guru</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($assignments as $a): ?>
            <tr><td><?= e($a['class_name']) ?></td><td><code><?= e($a['code']) ?></code> <?= e($a['subject']) ?></td><td><?= e($a['teacher']) ?></td>
                <td class="text-end"><?= delete_button('admin/penugasan/hapus', (int) $a['id'], 'Hapus penugasan ini? Jadwal dan nilai terkait akan ikut terhapus.') ?></td></tr>
        <?php endforeach; if (!$assignments) echo empty_row(4); ?>
        </tbody>
    </table></div>
</div>

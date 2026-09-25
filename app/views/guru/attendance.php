<div class="page-header"><h1><i class="bi bi-clipboard-check"></i> Input Absensi</h1></div>
<div class="card mb-3"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="guru/absensi">
        <div class="col-md-4"><label class="form-label small">Kelas</label>
            <select class="form-select" name="class_id">
                <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $classId) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label small">Tanggal</label>
            <input class="form-control" type="date" name="date" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Tampilkan</button></div>
    </form>
</div></div>

<?php if (!$classes): ?>
    <div class="alert alert-info">Anda belum ditugaskan mengajar di kelas mana pun.</div>
<?php else: ?>
<form method="post" action="<?= url('guru/absensi', ['class_id' => $classId, 'date' => $date]) ?>">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold"><?= tanggal($date, true) ?> · <?= count($students) ?> siswa</span>
            <button type="button" class="btn btn-sm btn-outline-success" id="allPresent"><i class="bi bi-check2-all"></i> Tandai semua hadir</button>
        </div>
        <div class="table-responsive"><table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>#</th><th>NIS</th><th>Nama</th><th>Status</th><th>Keterangan</th></tr></thead>
            <tbody>
            <?php foreach ($students as $i => $s): $cur = $existing[$s['id']]['status'] ?? ''; ?>
                <tr>
                    <td><?= $i + 1 ?></td><td><?= e($s['nis']) ?></td><td><?= e($s['name']) ?></td>
                    <td class="text-nowrap">
                        <?php foreach (ATTENDANCE_STATUS as $k => $label): ?>
                            <input type="radio" class="btn-check" name="status[<?= (int) $s['id'] ?>]" id="st<?= (int) $s['id'] . $k ?>" value="<?= $k ?>" <?= $cur === $k ? 'checked' : '' ?>>
                            <label class="btn btn-sm btn-outline-<?= ['H'=>'success','S'=>'info','I'=>'warning','A'=>'danger'][$k] ?>" for="st<?= (int) $s['id'] . $k ?>" title="<?= e($label) ?>"><?= $k ?></label>
                        <?php endforeach; ?>
                    </td>
                    <td><input class="form-control form-control-sm" name="note[<?= (int) $s['id'] ?>]" value="<?= e($existing[$s['id']]['note'] ?? '') ?>" maxlength="255"></td>
                </tr>
            <?php endforeach; if (!$students) echo empty_row(5, 'Belum ada siswa di kelas ini.'); ?>
            </tbody>
        </table></div>
        <?php if ($students): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span class="small text-muted">H = Hadir · S = Sakit · I = Izin · A = Alpa</span>
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Absensi</button>
        </div>
        <?php endif; ?>
    </div>
</form>
<script>
document.getElementById('allPresent')?.addEventListener('click', () => {
    document.querySelectorAll('input[type=radio][value=H]').forEach(r => r.checked = true);
});
</script>
<?php endif; ?>

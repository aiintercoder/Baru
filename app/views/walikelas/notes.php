<div class="page-header">
    <h1><i class="bi bi-journal-text"></i> Catatan Wali Kelas — <?= e($class['name']) ?></h1>
    <span class="badge text-bg-light border">TA <?= e($period['year']) ?> · <?= e($period['semester']) ?></span>
</div>
<form method="post">
    <?= csrf_field() ?>
    <div class="card">
        <div class="table-responsive"><table class="table mb-0">
            <thead class="table-light"><tr><th style="width: 30%">Siswa</th><th>Catatan untuk rapor</th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($s['name']) ?></div><div class="small text-muted">NIS <?= e($s['nis']) ?></div></td>
                    <td><textarea class="form-control form-control-sm" name="note[<?= (int) $s['id'] ?>]" rows="2" maxlength="2000"
                                  placeholder="mis. Pertahankan prestasimu, tingkatkan kedisiplinan…"><?= e($existing[$s['id']] ?? '') ?></textarea></td>
                </tr>
            <?php endforeach; if (!$students) echo empty_row(2); ?>
            </tbody>
        </table></div>
        <?php if ($students): ?><div class="card-footer bg-white text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Catatan</button></div><?php endif; ?>
    </div>
</form>

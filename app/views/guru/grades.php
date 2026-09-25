<?php $w = config('grade_weights'); ?>
<div class="page-header">
    <h1><i class="bi bi-pencil-square"></i> Input Nilai</h1>
    <span class="badge text-bg-light border">TA <?= e($period['year']) ?> · Semester <?= e($period['semester']) ?></span>
</div>
<?php if (!$assignments): ?>
    <div class="alert alert-info">Anda belum memiliki penugasan mengajar.</div>
<?php else: ?>
<div class="card mb-3"><div class="card-body">
    <form method="get" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="r" value="guru/nilai">
        <label class="small text-muted text-nowrap">Kelas & Mapel</label>
        <select class="form-select" name="assignment_id" onchange="this.form.submit()">
            <?php foreach ($assignments as $a): ?>
                <option value="<?= (int) $a['id'] ?>"<?= selected($a['id'], $current['id'] ?? '') ?>><?= e($a['class_name'] . ' — ' . $a['subject']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div></div>

<form method="post" action="<?= url('guru/nilai', ['assignment_id' => $current['id']]) ?>">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header bg-white">
            <span class="fw-semibold"><?= e($current['class_name'] . ' · ' . $current['subject']) ?></span>
            <span class="small text-muted ms-2">KKM <?= (int) $current['kkm'] ?> · Nilai akhir = Tugas <?= $w["task"] ?>% + UTS <?= $w["mid"] ?>% + UAS <?= $w["final"] ?>% (komponen kosong tidak dihitung)</span>
        </div>
        <div class="table-responsive"><table class="table table-hover table-grade mb-0">
            <thead class="table-light"><tr><th>#</th><th>NIS</th><th>Nama</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Akhir</th><th>Predikat</th></tr></thead>
            <tbody>
            <?php foreach ($students as $i => $s): $g = $grades[$s['id']] ?? [];
                $final = $g ? score_final($g['task_score'], $g['mid_score'], $g['final_score']) : null; ?>
                <tr>
                    <td><?= $i + 1 ?></td><td><?= e($s['nis']) ?></td><td><?= e($s['name']) ?></td>
                    <?php foreach (['task_score', 'mid_score', 'final_score'] as $k): ?>
                        <td><input class="form-control form-control-sm" type="number" min="0" max="100" step="0.01"
                                   name="score[<?= (int) $s['id'] ?>][<?= $k ?>]" value="<?= e(isset($g[$k]) ? (float) $g[$k] : '') ?>"></td>
                    <?php endforeach; ?>
                    <td class="fw-semibold <?= $final !== null && $final < $current['kkm'] ? 'text-danger' : '' ?>"><?= fmt_score($final) ?></td>
                    <td><?= predicate($final) ?></td>
                </tr>
            <?php endforeach; if (!$students) echo empty_row(8, 'Belum ada siswa di kelas ini.'); ?>
            </tbody>
        </table></div>
        <?php if ($students): ?>
        <div class="card-footer bg-white text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Nilai</button></div>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>

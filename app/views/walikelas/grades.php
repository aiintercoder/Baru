<div class="page-header">
    <h1><i class="bi bi-table"></i> Rekap Nilai Kelas <?= e($class['name']) ?></h1>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge text-bg-light border">TA <?= e($period['year']) ?> · <?= e($period['semester']) ?></span>
        <button type="button" class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
    </div>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-bordered table-hover table-sm mb-0 text-center">
        <thead class="table-light"><tr><th>#</th><th class="text-start">Nama</th>
            <?php foreach ($subjects as $sub): ?><th title="<?= e($sub['name']) ?>"><?= e($sub['code']) ?></th><?php endforeach; ?>
            <th>Rata-rata</th><th>Peringkat</th><th class="d-print-none"></th></tr></thead>
        <tbody>
        <?php foreach ($students as $i => $s): ?>
            <tr><td><?= $i + 1 ?></td><td class="text-start text-nowrap"><?= e($s['name']) ?></td>
                <?php foreach ($subjects as $sub): $v = $matrix[$s['id']][$sub['id']] ?? null; ?>
                    <td class="<?= $v !== null && $v < $sub['kkm'] ? 'text-danger fw-semibold' : '' ?>"><?= fmt_score($v) ?></td>
                <?php endforeach; ?>
                <td class="fw-bold"><?= fmt_score($averages[$s['id']]) ?></td>
                <td><?= $ranks[$s['id']] ?? '-' ?></td>
                <td class="d-print-none"><a href="<?= url('rapor', ['student_id' => $s['id']]) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-award"></i></a></td>
            </tr>
        <?php endforeach; if (!$students) echo empty_row(count($subjects) + 5); ?>
        </tbody>
    </table>
</div>
<div class="card-footer bg-white small text-muted">Nilai merah = di bawah KKM. Peringkat berdasarkan rata-rata nilai akhir.</div>
</div>

<div class="page-header">
    <div><h1><i class="bi bi-graph-up"></i> Laporan Nilai</h1><div class="small text-muted">Rata-rata nilai akhir per kelas · TA <?= e($period['year']) ?> <?= e($period['semester']) ?></div></div>
    <button type="button" class="btn btn-sm btn-outline-secondary d-print-none" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-bordered table-hover mb-0 text-center">
        <thead class="table-light"><tr><th class="text-start">Kelas</th>
            <?php foreach ($subjects as $s): ?><th title="<?= e($s['name']) ?>"><?= e($s['code']) ?></th><?php endforeach; ?>
            <th>Rata-rata Kelas</th></tr></thead>
        <tbody>
        <?php foreach ($classes as $c): $row = $acc[$c['id']]['subjects'] ?? []; $avgs = []; ?>
            <tr><td class="text-start fw-semibold"><?= e($c['name']) ?></td>
                <?php foreach ($subjects as $s): $cell = $row[$s['id']] ?? null; ?>
                    <?php if ($cell): $avg = $cell['sum'] / $cell['n']; $avgs[] = $avg; ?>
                        <td><?= fmt_score(round($avg, 1)) ?><?php if ($cell['below']): ?><div class="small text-danger"><?= (int) $cell['below'] ?> &lt; KKM</div><?php endif; ?></td>
                    <?php else: ?><td class="text-muted">-</td><?php endif; ?>
                <?php endforeach; ?>
                <td class="fw-bold"><?= $avgs ? fmt_score(round(array_sum($avgs) / count($avgs), 1)) : '-' ?></td></tr>
        <?php endforeach; if (!$classes) echo empty_row(count($subjects) + 2); ?>
        </tbody>
    </table>
</div>
<div class="card-footer bg-white small text-muted">Angka merah menunjukkan jumlah siswa dengan nilai akhir di bawah KKM.</div>
</div>

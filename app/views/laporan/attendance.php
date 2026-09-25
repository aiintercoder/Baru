<div class="page-header">
    <div><h1><i class="bi bi-bar-chart"></i> Laporan Absensi</h1><div class="small text-muted"><?= tanggal($from) ?> – <?= tanggal($to) ?></div></div>
    <?php require __DIR__ . '/range_filter.php'; ?>
</div>
<div class="card mb-3"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Kelas</th>
            <?php foreach (ATTENDANCE_STATUS as $label): ?><th class="text-center"><?= e($label) ?></th><?php endforeach; ?>
            <th style="width: 30%">Persentase Kehadiran</th></tr></thead>
        <tbody>
        <?php foreach ($classes as $c): $total = $c['H'] + $c['S'] + $c['I'] + $c['A']; $pct = $total ? round($c['H'] / $total * 100, 1) : 0; ?>
            <tr><td class="fw-semibold"><?= e($c['name']) ?></td>
                <?php foreach (array_keys(ATTENDANCE_STATUS) as $k): ?><td class="text-center"><?= $c[$k] ?></td><?php endforeach; ?>
                <td><?php if ($total): ?>
                    <div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height: 8px">
                        <div class="progress-bar bg-<?= $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') ?>" style="width: <?= $pct ?>%"></div></div>
                        <span class="small fw-semibold"><?= $pct ?>%</span></div>
                <?php else: ?><span class="text-muted small">Belum ada data</span><?php endif; ?></td></tr>
        <?php endforeach; if (!$classes) echo empty_row(6); ?>
        </tbody>
    </table>
</div></div>
<div class="card">
    <div class="card-header bg-white fw-semibold">Siswa dengan Ketidakhadiran Tanpa Keterangan Terbanyak</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Nama</th><th>NIS</th><th>Kelas</th><th class="text-center">Alpa</th></tr></thead>
        <tbody>
        <?php foreach ($absentees as $a): ?>
            <tr><td><?= e($a['name']) ?></td><td><?= e($a['nis']) ?></td><td><?= e($a['class_name'] ?? '-') ?></td><td class="text-center text-danger fw-bold"><?= (int) $a['n'] ?></td></tr>
        <?php endforeach; if (!$absentees) echo empty_row(4, 'Tidak ada siswa alpa pada periode ini.'); ?>
        </tbody>
    </table></div>
</div>

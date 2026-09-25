<?php
$billed = array_sum(array_map(fn($r) => (float) $r['billed'], $perClass));
$paid = array_sum(array_map(fn($r) => (float) $r['paid'], $perClass));
$periodTotal = array_sum(array_map(fn($p) => (float) $p['amount'], $payments));
?>
<div class="page-header">
    <div><h1><i class="bi bi-cash-stack"></i> Laporan Keuangan</h1><div class="small text-muted">Penerimaan <?= tanggal($from) ?> – <?= tanggal($to) ?></div></div>
    <?php require __DIR__ . '/range_filter.php'; ?>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><?= stat_card('Total Tagihan', rupiah($billed), 'receipt', 'primary') ?></div>
    <div class="col-md-3"><?= stat_card('Total Terbayar', rupiah($paid), 'check-circle', 'success') ?></div>
    <div class="col-md-3"><?= stat_card('Tunggakan', rupiah(max(0, $billed - $paid)), 'exclamation-triangle', 'danger') ?></div>
    <div class="col-md-3"><?= stat_card('Penerimaan Periode', rupiah($periodTotal), 'calendar-range', 'info') ?></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card h-100">
        <div class="card-header bg-white fw-semibold">Rekap per Kelas (seluruh tagihan)</div>
        <div class="table-responsive"><table class="table mb-0">
            <thead class="table-light"><tr><th>Kelas</th><th class="text-center">Tagihan</th><th class="text-end">Ditagih</th><th class="text-end">Terbayar</th><th class="text-end">Tunggakan</th><th class="text-end">%</th></tr></thead>
            <tbody>
            <?php foreach ($perClass as $r): $pct = $r['billed'] > 0 ? round($r['paid'] / $r['billed'] * 100) : 0; ?>
                <tr><td><?= e($r['class_name'] ?? 'Tanpa kelas') ?></td><td class="text-center"><?= (int) $r['invoice_count'] ?></td>
                    <td class="text-end"><?= rupiah($r['billed']) ?></td><td class="text-end"><?= rupiah($r['paid']) ?></td>
                    <td class="text-end text-danger"><?= rupiah(max(0, $r['billed'] - $r['paid'])) ?></td><td class="text-end"><?= $pct ?>%</td></tr>
            <?php endforeach; if (!$perClass) echo empty_row(6); ?>
            </tbody>
        </table></div>
    </div></div>
    <div class="col-lg-4"><div class="card h-100">
        <div class="card-header bg-white fw-semibold">Penerimaan per Metode</div>
        <ul class="list-group list-group-flush">
            <?php foreach ($byMethod as $m): ?>
                <li class="list-group-item d-flex justify-content-between"><span><?= e($m['method']) ?> <span class="badge text-bg-light border"><?= (int) $m['n'] ?></span></span><strong><?= rupiah($m['total']) ?></strong></li>
            <?php endforeach; if (!$byMethod): ?><li class="list-group-item text-muted">Belum ada penerimaan.</li><?php endif; ?>
        </ul>
    </div></div>
</div>
<div class="card">
    <div class="card-header bg-white fw-semibold">Rincian Penerimaan</div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Tagihan</th><th>Metode</th><th class="text-end">Jumlah</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr><td><?= tanggal($p['paid_at']) ?></td><td><?= e($p['student_name']) ?></td><td><?= e($p['class_name'] ?? '-') ?></td><td><?= e($p['title']) ?></td><td><?= e($p['method']) ?></td><td class="text-end"><?= rupiah($p['amount']) ?></td></tr>
        <?php endforeach; if (!$payments) echo empty_row(6); ?>
        </tbody>
    </table></div>
</div>

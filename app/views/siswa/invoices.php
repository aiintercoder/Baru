<?php
$total = array_sum(array_map(fn($i) => (float) $i['amount'], $invoices));
$paid = array_sum(array_map(fn($i) => (float) $i['paid'], $invoices));
?>
<div class="page-header"><h1><i class="bi bi-receipt"></i> Tagihan Sekolah</h1></div>
<?php require __DIR__ . '/student_banner.php'; ?>
<div class="row g-3 mb-3">
    <div class="col-md-4"><?= stat_card('Total Tagihan', rupiah($total), 'receipt', 'primary') ?></div>
    <div class="col-md-4"><?= stat_card('Sudah Dibayar', rupiah($paid), 'check-circle', 'success') ?></div>
    <div class="col-md-4"><?= stat_card('Sisa Tunggakan', rupiah(max(0, $total - $paid)), 'exclamation-triangle', $total - $paid > 0 ? 'danger' : 'success') ?></div>
</div>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Daftar Tagihan</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Tagihan</th><th>Jatuh Tempo</th><th class="text-end">Jumlah</th><th class="text-end">Dibayar</th><th class="text-end">Sisa</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $i): $sisa = max(0, (float) $i['amount'] - (float) $i['paid']);
            $late = $sisa > 0 && $i['due_date'] && $i['due_date'] < date('Y-m-d'); ?>
            <tr><td><?= e($i['title']) ?></td>
                <td class="<?= $late ? 'text-danger fw-semibold' : '' ?>"><?= tanggal($i['due_date']) ?><?= $late ? ' <i class="bi bi-alarm"></i>' : '' ?></td>
                <td class="text-end"><?= rupiah($i['amount']) ?></td><td class="text-end"><?= rupiah($i['paid']) ?></td>
                <td class="text-end"><?= rupiah($sisa) ?></td><td><?= invoice_badge((float) $i['amount'], (float) $i['paid']) ?></td></tr>
        <?php endforeach; if (!$invoices) echo empty_row(6, 'Tidak ada tagihan.'); ?>
        </tbody>
    </table></div>
</div>
<div class="card">
    <div class="card-header bg-white fw-semibold">Riwayat Pembayaran</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>Tagihan</th><th>Metode</th><th class="text-end">Jumlah</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr><td><?= tanggal($p['paid_at']) ?></td><td><?= e($p['title']) ?></td><td><?= e($p['method']) ?></td><td class="text-end"><?= rupiah($p['amount']) ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= url('keuangan/kwitansi', ['id' => $p['id']]) ?>" target="_blank"><i class="bi bi-printer"></i> Kwitansi</a></td></tr>
        <?php endforeach; if (!$payments) echo empty_row(5, 'Belum ada pembayaran.'); ?>
        </tbody>
    </table></div>
</div>
<p class="small text-muted mt-3"><i class="bi bi-info-circle"></i> Pembayaran dilakukan melalui bagian Tata Usaha. Hubungi TU bila ada ketidaksesuaian data.</p>

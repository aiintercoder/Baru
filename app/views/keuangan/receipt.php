<div class="d-print-none mb-3 d-flex gap-2">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Kwitansi</button>
</div>
<div class="card shadow-sm" style="max-width: 720px">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
            <div>
                <div class="h5 fw-bold mb-0"><?= e(setting('school_name', config('app_name'))) ?></div>
                <div class="small text-muted"><?= e(setting('school_address')) ?> <?= setting('school_phone') ? '· Telp ' . e(setting('school_phone')) : '' ?></div>
            </div>
            <div class="text-end"><div class="h5 mb-0">KWITANSI</div><code><?= e($payment['number']) ?></code></div>
        </div>
        <table class="table table-borderless table-sm">
            <tr><td style="width: 35%">Telah terima dari</td><td>: <strong><?= e($payment['student_name']) ?></strong> (NIS <?= e($payment['nis']) ?>, Kelas <?= e($payment['class_name'] ?? '-') ?>)</td></tr>
            <tr><td>Untuk pembayaran</td><td>: <?= e($payment['title']) ?></td></tr>
            <tr><td>Metode</td><td>: <?= e($payment['method']) ?></td></tr>
            <?php if ($payment['note']): ?><tr><td>Keterangan</td><td>: <?= e($payment['note']) ?></td></tr><?php endif; ?>
            <tr><td>Jumlah</td><td>: <span class="fs-5 fw-bold"><?= rupiah($payment['amount']) ?></span></td></tr>
        </table>
        <div class="d-flex justify-content-end mt-4">
            <div class="text-center small">
                <?= tanggal($payment['paid_at']) ?><br>Petugas Tata Usaha<br><br><br>
                <strong><?= e($payment['receiver'] ?? '................................') ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="page-header">
    <h1><i class="bi bi-cash-coin"></i> Pembayaran Tagihan</h1>
    <a class="btn btn-outline-secondary" href="<?= url('keuangan/tagihan') ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-5">Siswa</dt><dd class="col-7"><?= e($invoice['student_name']) ?> (<?= e($invoice['nis']) ?>)</dd>
                <dt class="col-5">Kelas</dt><dd class="col-7"><?= e($invoice['class_name'] ?? '-') ?></dd>
                <dt class="col-5">Tagihan</dt><dd class="col-7"><?= e($invoice['title']) ?></dd>
                <dt class="col-5">Jatuh Tempo</dt><dd class="col-7"><?= tanggal($invoice['due_date']) ?></dd>
                <dt class="col-5">Jumlah</dt><dd class="col-7"><?= rupiah($invoice['amount']) ?></dd>
                <dt class="col-5">Sudah Dibayar</dt><dd class="col-7 text-success"><?= rupiah($invoice['paid']) ?></dd>
                <dt class="col-5">Sisa</dt><dd class="col-7 fw-bold <?= $remaining > 0 ? 'text-danger' : 'text-success' ?>"><?= rupiah($remaining) ?></dd>
                <dt class="col-5">Status</dt><dd class="col-7"><?= invoice_badge((float) $invoice['amount'], (float) $invoice['paid']) ?></dd>
            </dl>
        </div></div>
        <?php if ($remaining > 0): ?>
        <div class="card"><div class="card-header bg-white fw-semibold">Catat Pembayaran</div><div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-2"><label class="form-label">Jumlah (Rp)</label>
                    <input class="form-control" name="amount" inputmode="numeric" value="<?= (int) $remaining ?>" required></div>
                <div class="mb-2"><label class="form-label">Tanggal Bayar</label>
                    <input class="form-control" type="date" name="paid_at" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
                <div class="mb-2"><label class="form-label">Metode</label>
                    <select class="form-select" name="method"><?php foreach (PAYMENT_METHODS as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select></div>
                <div class="mb-3"><label class="form-label">Keterangan</label>
                    <input class="form-control" name="note" maxlength="255"></div>
                <button class="btn btn-success w-100"><i class="bi bi-check2-circle"></i> Simpan Pembayaran</button>
            </form>
        </div></div>
        <?php endif; ?>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header bg-white fw-semibold">Riwayat Pembayaran</div>
            <div class="table-responsive"><table class="table mb-0">
                <thead class="table-light"><tr><th>No. Kwitansi</th><th>Tanggal</th><th>Metode</th><th class="text-end">Jumlah</th><th>Petugas</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr><td><code><?= e(receipt_number((int) $p['id'], $p['paid_at'])) ?></code></td><td><?= tanggal($p['paid_at']) ?></td><td><?= e($p['method']) ?></td>
                        <td class="text-end"><?= rupiah($p['amount']) ?></td><td class="small"><?= e($p['receiver'] ?? '-') ?></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= url('keuangan/kwitansi', ['id' => $p['id']]) ?>"><i class="bi bi-printer"></i></a>
                            <?= delete_button('keuangan/bayar/hapus', (int) $p['id'], 'Batalkan pembayaran ini?') ?>
                        </td></tr>
                <?php endforeach; if (!$payments) echo empty_row(6, 'Belum ada pembayaran.'); ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

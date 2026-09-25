<div class="page-header">
    <h1><i class="bi bi-receipt"></i> Tagihan & Pembayaran</h1>
    <a class="btn btn-primary" href="<?= url('keuangan/tagihan/form') ?>"><i class="bi bi-plus-lg"></i> Buat Tagihan</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form method="get" class="row g-2">
        <input type="hidden" name="r" value="keuangan/tagihan">
        <div class="col-md-3"><select class="form-select" name="class_id">
            <option value="">Semua kelas</option>
            <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $classId) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-3"><select class="form-select" name="status">
            <option value="">Semua status</option>
            <option value="belum"<?= selected('belum', $status) ?>>Belum lunas</option>
            <option value="sebagian"<?= selected('sebagian', $status) ?>>Dibayar sebagian</option>
            <option value="lunas"<?= selected('lunas', $status) ?>>Lunas</option>
        </select></div>
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama siswa, NIS, atau tagihan"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filter</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Siswa</th><th>Kelas</th><th>Tagihan</th><th>Jatuh Tempo</th><th class="text-end">Jumlah</th><th class="text-end">Dibayar</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $i): ?>
            <tr>
                <td><div class="fw-semibold"><?= e($i['student_name']) ?></div><div class="small text-muted"><?= e($i['nis']) ?></div></td>
                <td><?= e($i['class_name'] ?? '-') ?></td>
                <td><?= e($i['title']) ?></td>
                <td><?= tanggal($i['due_date']) ?></td>
                <td class="text-end"><?= rupiah($i['amount']) ?></td>
                <td class="text-end"><?= rupiah($i['paid']) ?></td>
                <td><?= invoice_badge((float) $i['amount'], (float) $i['paid']) ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-success" href="<?= url('keuangan/bayar', ['id' => $i['id']]) ?>"><i class="bi bi-cash"></i> Bayar</a>
                    <?php if ((float) $i['paid'] == 0): ?><?= delete_button('keuangan/tagihan/hapus', (int) $i['id'], 'Hapus tagihan ini?') ?><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; if (!$invoices) echo empty_row(8, 'Tidak ada tagihan.'); ?>
        </tbody>
    </table>
</div>
<div class="card-footer bg-white small text-muted"><?= count($invoices) ?> tagihan ·
    Total <?= rupiah(array_sum(array_map(fn($i) => (float) $i['amount'], $invoices))) ?> ·
    Terbayar <?= rupiah(array_sum(array_map(fn($i) => (float) $i['paid'], $invoices))) ?></div>
</div>

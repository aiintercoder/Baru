<div class="page-header">
    <h1><i class="bi bi-envelope-paper"></i> Arsip Surat Masuk & Keluar</h1>
    <?php if ($canEdit): ?><a class="btn btn-primary" href="<?= url('surat/form') ?>"><i class="bi bi-plus-lg"></i> Catat Surat</a><?php endif; ?>
</div>
<div class="card mb-3"><div class="card-body">
    <form method="get" class="row g-2">
        <input type="hidden" name="r" value="surat">
        <div class="col-md-3"><select class="form-select" name="direction">
            <option value="">Semua</option>
            <option value="masuk"<?= selected('masuk', $direction) ?>>Surat Masuk</option>
            <option value="keluar"<?= selected('keluar', $direction) ?>>Surat Keluar</option>
        </select></div>
        <div class="col-md-7"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nomor, pengirim/penerima, atau perihal"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Cari</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Jenis</th><th>Nomor</th><th>Tanggal</th><th>Dari/Kepada</th><th>Perihal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($letters as $l): ?>
            <tr>
                <td><?= $l['direction'] === 'masuk' ? '<span class="badge text-bg-info">Masuk</span>' : '<span class="badge text-bg-secondary">Keluar</span>' ?></td>
                <td><code><?= e($l['letter_number']) ?></code></td>
                <td class="text-nowrap"><?= tanggal($l['letter_date']) ?></td>
                <td><?= e($l['party']) ?></td>
                <td><?= e($l['subject']) ?><?php if ($l['note']): ?><div class="small text-muted"><?= e($l['note']) ?></div><?php endif; ?></td>
                <td class="text-end text-nowrap"><?php if ($canEdit): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('surat/form', ['id' => $l['id']]) ?>"><i class="bi bi-pencil"></i></a>
                    <?= delete_button('surat/hapus', (int) $l['id'], 'Hapus data surat ini?') ?>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; if (!$letters) echo empty_row(6); ?>
        </tbody>
    </table>
</div></div>

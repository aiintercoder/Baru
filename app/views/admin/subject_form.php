<div class="page-header"><h1><?= $isNew ? 'Tambah' : 'Ubah' ?> Mata Pelajaran</h1></div>
<div class="card" style="max-width: 560px"><div class="card-body">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Kode</label>
            <input class="form-control" name="code" value="<?= e($subject['code'] ?? '') ?>" placeholder="mis. MTK" required></div>
        <div class="mb-3"><label class="form-label">Nama Mata Pelajaran</label>
            <input class="form-control" name="name" value="<?= e($subject['name'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">KKM (Kriteria Ketuntasan Minimal)</label>
            <input class="form-control" type="number" min="0" max="100" name="kkm" value="<?= e($subject['kkm'] ?? 75) ?>" required></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        <a class="btn btn-link" href="<?= url('admin/mapel') ?>">Batal</a>
    </form>
</div></div>

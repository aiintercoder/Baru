<div class="page-header"><h1><?= !empty($item['id']) ? 'Ubah' : 'Buat' ?> Pengumuman</h1></div>
<div class="card" style="max-width: 760px">
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">Judul</label>
                <input class="form-control" name="title" maxlength="150" value="<?= e($item['title'] ?? '') ?>" required></div>
            <div class="mb-3"><label class="form-label">Ditujukan kepada</label>
                <select class="form-select" name="audience">
                    <?php foreach (AUDIENCES as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= selected($k, $item['audience'] ?? 'semua') ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="mb-3"><label class="form-label">Isi Pengumuman</label>
                <textarea class="form-control" name="body" rows="8" required><?= e($item['body'] ?? '') ?></textarea></div>
            <button class="btn btn-primary"><i class="bi bi-send"></i> Simpan</button>
            <a class="btn btn-link" href="<?= url('pengumuman') ?>">Batal</a>
        </form>
    </div>
</div>

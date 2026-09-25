<div class="page-header"><h1><?= $isNew ? 'Tambah' : 'Ubah' ?> Kelas</h1></div>
<div class="card" style="max-width: 560px"><div class="card-body">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Nama Kelas</label>
            <input class="form-control" name="name" value="<?= e($class['name'] ?? '') ?>" placeholder="mis. VII-A" required></div>
        <div class="mb-3"><label class="form-label">Tingkat</label>
            <input class="form-control" type="number" min="1" max="12" name="level" value="<?= e($class['level'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label">Wali Kelas</label>
            <select class="form-select" name="homeroom_teacher_id">
                <option value="">— Belum ditentukan —</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int) $t['id'] ?>"<?= selected($t['id'], $class['homeroom_teacher_id'] ?? '') ?>><?= e($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Hanya pengguna dengan peran "Wali Kelas" yang dapat dipilih.</div></div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        <a class="btn btn-link" href="<?= url('admin/kelas') ?>">Batal</a>
    </form>
</div></div>

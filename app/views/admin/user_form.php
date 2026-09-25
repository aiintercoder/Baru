<?php $currentRole = $user['role'] ?? (isset(ROLES[$presetRole]) ? $presetRole : 'siswa'); ?>
<div class="page-header"><h1><?= $isNew ? 'Tambah' : 'Ubah' ?> Pengguna</h1></div>
<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" class="row g-3">
    <?= csrf_field() ?>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header bg-white fw-semibold">Data Akun</div>
            <div class="card-body">
                <div class="mb-2"><label class="form-label">Peran</label>
                    <select class="form-select" name="role" id="role" required>
                        <?php foreach (ROLES as $k => $label): ?>
                            <option value="<?= e($k) ?>"<?= selected($k, $currentRole) ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="mb-2"><label class="form-label">Nama Lengkap</label>
                    <input class="form-control" name="name" value="<?= e($user['name'] ?? '') ?>" required></div>
                <div class="mb-2"><label class="form-label">Username</label>
                    <input class="form-control" name="username" value="<?= e($user['username'] ?? '') ?>" pattern="[a-zA-Z0-9._\-]{3,50}" required></div>
                <div class="mb-2"><label class="form-label">Password <?= $isNew ? '' : '<small class="text-muted">(kosongkan bila tidak diubah)</small>' ?></label>
                    <input class="form-control" type="password" name="password" minlength="8" <?= $isNew ? 'required' : '' ?> autocomplete="new-password"></div>
                <div class="mb-2 staff-field"><label class="form-label">NIP</label>
                    <input class="form-control" name="nip" value="<?= e($user['nip'] ?? '') ?>"></div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= e($user['email'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">No. HP</label>
                        <input class="form-control" name="phone" value="<?= e($user['phone'] ?? '') ?>"></div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" name="active" id="active" <?= ($user['active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active">Akun aktif</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 student-field">
        <div class="card h-100"><div class="card-header bg-white fw-semibold">Data Murid</div>
            <div class="card-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-6"><label class="form-label">NIS</label>
                        <input class="form-control" name="nis" value="<?= e($student['nis'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Kelas</label>
                        <select class="form-select" name="class_id">
                            <option value="">— Belum ada —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $student['class_id'] ?? '') ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                </div>
                <div class="mb-2"><label class="form-label">Akun Orang Tua / Wali</label>
                    <select class="form-select" name="parent_id">
                        <option value="">— Belum terhubung —</option>
                        <?php foreach ($parents as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"<?= selected($p['id'], $student['parent_id'] ?? '') ?>><?= e($p['name']) ?><?= $p['phone'] ? ' · ' . e($p['phone']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Buat akun dengan peran "Orang Tua Murid" terlebih dahulu bila belum ada.</div></div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4"><label class="form-label">Jenis Kelamin</label>
                        <select class="form-select" name="gender">
                            <option value="">-</option>
                            <option value="L"<?= selected('L', $student['gender'] ?? '') ?>>Laki-laki</option>
                            <option value="P"<?= selected('P', $student['gender'] ?? '') ?>>Perempuan</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Tempat Lahir</label>
                        <input class="form-control" name="birth_place" value="<?= e($student['birth_place'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Tanggal Lahir</label>
                        <input class="form-control" type="date" name="birth_date" value="<?= e($student['birth_date'] ?? '') ?>"></div>
                </div>
                <div><label class="form-label">Alamat</label>
                    <textarea class="form-control" name="address" rows="2"><?= e($student['address'] ?? '') ?></textarea></div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        <a class="btn btn-link" href="<?= url('admin/pengguna') ?>">Batal</a>
    </div>
</form>
<script>
(function () {
    const role = document.getElementById('role');
    const toggle = () => {
        const isStudent = role.value === 'siswa';
        document.querySelectorAll('.student-field').forEach(el => el.classList.toggle('d-none', !isStudent));
        document.querySelectorAll('.staff-field').forEach(el => el.classList.toggle('d-none', ['siswa', 'orang_tua'].includes(role.value)));
    };
    role.addEventListener('change', toggle);
    toggle();
})();
</script>

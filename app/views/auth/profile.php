<div class="page-header"><h1><i class="bi bi-person-circle"></i> Profil Saya</h1></div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Data Akun</div>
            <div class="card-body">
                <dl class="row mb-3">
                    <dt class="col-sm-4">Nama</dt><dd class="col-sm-8"><?= e($me['name']) ?></dd>
                    <dt class="col-sm-4">Username</dt><dd class="col-sm-8"><?= e($me['username']) ?></dd>
                    <dt class="col-sm-4">Peran</dt><dd class="col-sm-8"><?= e(role_label($me['role'])) ?></dd>
                    <?php if ($me['nip']): ?><dt class="col-sm-4">NIP</dt><dd class="col-sm-8"><?= e($me['nip']) ?></dd><?php endif; ?>
                    <?php if ($student): ?>
                        <dt class="col-sm-4">NIS</dt><dd class="col-sm-8"><?= e($student['nis']) ?></dd>
                        <dt class="col-sm-4">Kelas</dt><dd class="col-sm-8"><?= e($student['class_name'] ?? '-') ?></dd>
                    <?php endif; ?>
                </dl>
                <form method="post">
                    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                    <div class="mb-2"><label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= e($me['email']) ?>"></div>
                    <div class="mb-3"><label class="form-label">No. HP</label>
                        <input class="form-control" name="phone" value="<?= e($me['phone']) ?>"></div>
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Profil</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Ganti Password</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?><input type="hidden" name="action" value="password">
                    <div class="mb-2"><label class="form-label">Password Lama</label>
                        <input class="form-control" type="password" name="old_password" required></div>
                    <div class="mb-2"><label class="form-label">Password Baru (min. 8 karakter)</label>
                        <input class="form-control" type="password" name="new_password" minlength="8" required></div>
                    <div class="mb-3"><label class="form-label">Konfirmasi Password Baru</label>
                        <input class="form-control" type="password" name="confirm_password" minlength="8" required></div>
                    <button class="btn btn-warning"><i class="bi bi-key"></i> Ganti Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

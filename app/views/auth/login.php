<div class="login-wrap d-flex align-items-center justify-content-center p-3">
    <div class="card shadow-lg border-0" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="display-5 text-primary"><i class="bi bi-mortarboard-fill"></i></div>
                <h1 class="h4 fw-bold mb-1"><?= e(setting('school_name', config('app_name'))) ?></h1>
                <p class="text-muted small mb-0">Sistem Informasi Akademik & Administrasi Sekolah</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= url('login') ?>" autocomplete="on">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input class="form-control" id="username" name="username" value="<?= e($username) ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input class="form-control" id="password" type="password" name="password" required>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
            </form>
            <p class="text-center text-muted small mt-4 mb-0">
                Masuk sebagai Murid, Guru, Wali Kelas, Orang Tua, Administrasi, Kepala Sekolah, atau Tata Usaha.
            </p>
        </div>
    </div>
</div>

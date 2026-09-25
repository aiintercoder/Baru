<div class="page-header">
    <h1><i class="bi bi-people"></i> Manajemen Pengguna</h1>
    <a class="btn btn-primary" href="<?= url('admin/pengguna/form', $role ? ['role' => $role] : []) ?>"><i class="bi bi-person-plus"></i> Tambah Pengguna</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2" method="get">
        <input type="hidden" name="r" value="admin/pengguna">
        <div class="col-md-4">
            <select class="form-select" name="role" onchange="this.form.submit()">
                <option value="">Semua peran</option>
                <?php foreach (ROLES as $k => $label): ?>
                    <option value="<?= e($k) ?>"<?= selected($k, $role) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, username, NIS, atau NIP"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Cari</button></div>
    </form>
</div></div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nama</th><th>Username</th><th>Peran</th><th>NIS/NIP</th><th>Kelas</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= e($u['name']) ?></td>
                    <td><code><?= e($u['username']) ?></code></td>
                    <td><?= e(role_label($u['role'])) ?></td>
                    <td><?= e($u['nis'] ?? $u['nip'] ?? '-') ?></td>
                    <td><?= e($u['class_name'] ?? '-') ?></td>
                    <td><?= (int) $u['active'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/pengguna/form', ['id' => $u['id']]) ?>"><i class="bi bi-pencil"></i></a>
                        <?= delete_button('admin/pengguna/hapus', (int) $u['id'], 'Hapus pengguna ' . $u['name'] . '? Seluruh data terkait akan ikut terhapus.') ?>
                    </td>
                </tr>
            <?php endforeach; if (!$users) echo empty_row(7); ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white small text-muted"><?= count($users) ?> pengguna</div>
</div>

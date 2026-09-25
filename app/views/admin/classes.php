<div class="page-header">
    <h1><i class="bi bi-door-open"></i> Data Kelas</h1>
    <a class="btn btn-primary" href="<?= url('admin/kelas/form') ?>"><i class="bi bi-plus-lg"></i> Tambah Kelas</a>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Kelas</th><th>Tingkat</th><th>Wali Kelas</th><th class="text-center">Siswa</th><th class="text-center">Mapel</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($classes as $c): ?>
            <tr>
                <td class="fw-semibold"><?= e($c['name']) ?></td>
                <td><?= (int) $c['level'] ?></td>
                <td><?= $c['homeroom_name'] ? e($c['homeroom_name']) : '<span class="text-danger small">Belum ditentukan</span>' ?></td>
                <td class="text-center"><a href="<?= url('data/siswa', ['class_id' => $c['id']]) ?>"><?= (int) $c['student_count'] ?></a></td>
                <td class="text-center"><a href="<?= url('admin/penugasan', ['class_id' => $c['id']]) ?>"><?= (int) $c['subject_count'] ?></a></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/jadwal', ['class_id' => $c['id']]) ?>" title="Jadwal"><i class="bi bi-calendar3"></i></a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/kelas/form', ['id' => $c['id']]) ?>"><i class="bi bi-pencil"></i></a>
                    <?= delete_button('admin/kelas/hapus', (int) $c['id'], 'Hapus kelas ' . $c['name'] . '?') ?>
                </td>
            </tr>
        <?php endforeach; if (!$classes) echo empty_row(6); ?>
        </tbody>
    </table>
</div></div>

<div class="page-header">
    <h1><i class="bi bi-book"></i> Mata Pelajaran</h1>
    <a class="btn btn-primary" href="<?= url('admin/mapel/form') ?>"><i class="bi bi-plus-lg"></i> Tambah Mapel</a>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th class="text-center">KKM</th><th class="text-center">Dipakai di Kelas</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($subjects as $s): ?>
            <tr>
                <td><code><?= e($s['code']) ?></code></td>
                <td><?= e($s['name']) ?></td>
                <td class="text-center"><?= (int) $s['kkm'] ?></td>
                <td class="text-center"><?= (int) $s['used'] ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/mapel/form', ['id' => $s['id']]) ?>"><i class="bi bi-pencil"></i></a>
                    <?= delete_button('admin/mapel/hapus', (int) $s['id'], 'Hapus mata pelajaran ' . $s['name'] . '?') ?>
                </td>
            </tr>
        <?php endforeach; if (!$subjects) echo empty_row(5); ?>
        </tbody>
    </table>
</div></div>

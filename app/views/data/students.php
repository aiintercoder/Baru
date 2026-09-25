<?php $canReport = has_role('admin', 'kepala_sekolah'); ?>
<div class="page-header">
    <h1><i class="bi bi-mortarboard"></i> Data Siswa</h1>
    <?php if (has_role('admin')): ?><a class="btn btn-primary" href="<?= url('admin/pengguna/form', ['role' => 'siswa']) ?>"><i class="bi bi-person-plus"></i> Tambah Siswa</a><?php endif; ?>
</div>
<div class="card mb-3"><div class="card-body">
    <form method="get" class="row g-2">
        <input type="hidden" name="r" value="data/siswa">
        <div class="col-md-3"><select class="form-select" name="class_id">
            <option value="">Semua kelas</option>
            <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $classId) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-7"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau NIS"></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Cari</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>TTL</th><th>Orang Tua</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= e($s['nis']) ?></td>
                <td class="fw-semibold"><?= e($s['name']) ?></td>
                <td><?= e($s['gender'] ?? '-') ?></td>
                <td><?= e($s['class_name'] ?? '-') ?></td>
                <td class="small"><?= e($s['birth_place'] ?? '-') ?>, <?= tanggal($s['birth_date']) ?></td>
                <td class="small"><?= e($s['parent_name'] ?? '-') ?><?= $s['parent_phone'] ? '<br>' . e($s['parent_phone']) : '' ?></td>
                <td><?= (int) $s['active'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                <td class="text-end text-nowrap">
                    <?php if ($canReport): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('rapor', ['student_id' => $s['id']]) ?>" title="Rapor"><i class="bi bi-award"></i></a><?php endif; ?>
                    <?php if (has_role('admin')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/pengguna/form', ['id' => $s['user_id']]) ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
                    <?php if (has_role('tata_usaha')): ?><a class="btn btn-sm btn-outline-success" href="<?= url('keuangan/tagihan', ['q' => $s['nis']]) ?>" title="Tagihan"><i class="bi bi-receipt"></i></a><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; if (!$students) echo empty_row(8); ?>
        </tbody>
    </table>
</div>
<div class="card-footer bg-white small text-muted"><?= count($students) ?> siswa</div>
</div>

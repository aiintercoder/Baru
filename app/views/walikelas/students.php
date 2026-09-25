<div class="page-header">
    <h1><i class="bi bi-people"></i> Siswa Kelas <?= e($class['name']) ?></h1>
    <span class="text-muted small"><?= count($students) ?> siswa · rekap kehadiran semester aktif</span>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>NIS</th><th>Nama</th><th>L/P</th><th>Orang Tua</th>
            <th class="text-center">H</th><th class="text-center">S</th><th class="text-center">I</th><th class="text-center">A</th><th class="text-end">Tunggakan</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($students as $i => $s): ?>
            <tr>
                <td><?= $i + 1 ?></td><td><?= e($s['nis']) ?></td><td class="fw-semibold"><?= e($s['name']) ?></td><td><?= e($s['gender'] ?? '-') ?></td>
                <td><?= e($s['parent_name'] ?? '-') ?><?php if ($s['parent_phone']): ?><div class="small text-muted"><i class="bi bi-telephone"></i> <?= e($s['parent_phone']) ?></div><?php endif; ?></td>
                <?php foreach (['H', 'S', 'I', 'A'] as $k): ?>
                    <td class="text-center<?= $k === 'A' && $s['att']['A'] >= 3 ? ' text-danger fw-bold' : '' ?>"><?= (int) $s['att'][$k] ?></td>
                <?php endforeach; ?>
                <td class="text-end<?= $s['outstanding'] > 0 ? ' text-danger' : '' ?>"><?= rupiah($s['outstanding']) ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('rapor', ['student_id' => $s['id']]) ?>"><i class="bi bi-award"></i> Rapor</a></td>
            </tr>
        <?php endforeach; if (!$students) echo empty_row(11); ?>
        </tbody>
    </table>
</div></div>

<div class="page-header"><h1><i class="bi bi-calendar-week"></i> Jadwal Mengajar</h1></div>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Mata Pelajaran yang Diampu</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Kelas</th><th>Mata Pelajaran</th><th class="text-center">Siswa</th><th class="text-center">KKM</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($assignments as $a): ?>
            <tr><td><?= e($a['class_name']) ?></td><td><code><?= e($a['code']) ?></code> <?= e($a['subject']) ?></td>
                <td class="text-center"><?= (int) $a['student_count'] ?></td><td class="text-center"><?= (int) $a['kkm'] ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="<?= url('guru/nilai', ['assignment_id' => $a['id']]) ?>"><i class="bi bi-pencil-square"></i> Nilai</a>
                    <a class="btn btn-sm btn-outline-success" href="<?= url('guru/absensi', ['class_id' => $a['class_id']]) ?>"><i class="bi bi-clipboard-check"></i> Absensi</a>
                </td></tr>
        <?php endforeach; if (!$assignments) echo empty_row(5, 'Anda belum memiliki penugasan mengajar.'); ?>
        </tbody>
    </table></div>
</div>
<?php $canDelete = false; require BASE_PATH . '/app/views/shared/schedule_grid.php'; ?>

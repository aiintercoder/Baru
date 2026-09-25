<div class="page-header">
    <h1><i class="bi bi-calendar3"></i> Jadwal Pelajaran</h1>
    <form method="get" class="d-flex gap-2 align-items-center">
        <input type="hidden" name="r" value="admin/jadwal">
        <select class="form-select" name="class_id" onchange="this.form.submit()">
            <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $classId) ?>>Kelas <?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
    </form>
</div>
<?php if (!$classes): ?>
    <div class="alert alert-info">Belum ada kelas. Tambahkan kelas terlebih dahulu.</div>
<?php else: ?>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Tambah Jadwal</div>
    <div class="card-body">
        <?php if (!$assignments): ?>
            <p class="text-muted mb-0">Kelas ini belum memiliki penugasan guru. <a href="<?= url('admin/penugasan', ['class_id' => $classId]) ?>">Atur penugasan</a>.</p>
        <?php else: ?>
        <form method="post" class="row g-2">
            <?= csrf_field() ?>
            <div class="col-md-4"><select class="form-select" name="assignment_id" required>
                <?php foreach ($assignments as $a): ?><option value="<?= (int) $a['id'] ?>"><?= e($a['subject'] . ' — ' . $a['teacher']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="col-md-2"><select class="form-select" name="day">
                <?php foreach (DAYS as $k => $d): ?><option value="<?= $k ?>"><?= e($d) ?></option><?php endforeach; ?>
            </select></div>
            <div class="col-md-2"><input class="form-control" type="time" name="start_time" value="07:00" required></div>
            <div class="col-md-2"><input class="form-control" type="time" name="end_time" value="08:30" required></div>
            <div class="col-md-1"><input class="form-control" name="room" placeholder="Ruang"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button></div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require BASE_PATH . '/app/views/shared/schedule_grid.php'; ?>
<?php endif; ?>

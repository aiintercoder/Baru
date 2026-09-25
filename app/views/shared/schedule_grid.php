<?php
/**
 * Tabel jadwal per hari. Variabel: $schedules (day, start_time, end_time, subject, teacher|class_name, room),
 * opsional $canDelete (bool).
 */
$byDay = [];
foreach ($schedules as $s) {
    $byDay[(int) $s['day']][] = $s;
}
$canDelete = $canDelete ?? has_role('admin');
?>
<div class="row g-3">
    <?php foreach (DAYS as $d => $dayName): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-white fw-semibold<?= (int) date('N') === $d ? ' text-primary' : '' ?>">
                    <?= e($dayName) ?><?= (int) date('N') === $d ? ' <span class="badge text-bg-primary">Hari ini</span>' : '' ?>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($byDay[$d] ?? [] as $s): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="small text-muted"><?= e($s['start_time']) ?>–<?= e($s['end_time']) ?><?= !empty($s['room']) ? ' · ' . e($s['room']) : '' ?></div>
                                <div class="fw-semibold"><?= e($s['subject']) ?></div>
                                <div class="small"><?= e($s['teacher'] ?? $s['class_name'] ?? '') ?></div>
                            </div>
                            <?php if ($canDelete): ?><?= delete_button('admin/jadwal/hapus', (int) $s['id'], 'Hapus jadwal ini?') ?><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($byDay[$d])): ?><li class="list-group-item text-muted small">Tidak ada jadwal</li><?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="page-header"><h1><i class="bi bi-calendar-check"></i> Kehadiran</h1></div>
<?php require __DIR__ . '/student_banner.php'; ?>
<div class="row g-3 mb-3">
    <?php foreach (ATTENDANCE_STATUS as $k => $label): ?>
        <div class="col-6 col-md-3"><?= stat_card($label . ' (semester ' . $period['semester'] . ')', $semesterCounts[$k], ['H'=>'check-circle','S'=>'thermometer-half','I'=>'envelope','A'=>'x-circle'][$k], ['H'=>'success','S'=>'info','I'=>'warning','A'=>'danger'][$k]) ?></div>
    <?php endforeach; ?>
</div>
<div class="card">
    <div class="card-header bg-white fw-semibold">Riwayat Kehadiran</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead>
        <tbody>
        <?php foreach ($records as $r): ?>
            <tr><td><?= tanggal($r['att_date'], true) ?></td><td><?= status_badge($r['status']) ?></td><td><?= e($r['note'] ?? '') ?></td></tr>
        <?php endforeach; if (!$records) echo empty_row(3, 'Belum ada catatan kehadiran.'); ?>
        </tbody>
    </table></div>
</div>

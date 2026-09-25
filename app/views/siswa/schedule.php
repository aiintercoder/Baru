<div class="page-header"><h1><i class="bi bi-calendar-week"></i> Jadwal Pelajaran</h1></div>
<?php require __DIR__ . '/student_banner.php'; ?>
<?php if (!$student['class_id']): ?>
    <div class="alert alert-info">Siswa belum ditempatkan di kelas.</div>
<?php else: $canDelete = false; require BASE_PATH . '/app/views/shared/schedule_grid.php'; endif; ?>

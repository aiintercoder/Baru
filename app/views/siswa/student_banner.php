<div class="alert alert-light border d-flex flex-wrap gap-3 align-items-center py-2 mb-3">
    <span><i class="bi bi-person-vcard"></i> <strong><?= e($student['name']) ?></strong></span>
    <span class="text-muted small">NIS <?= e($student['nis']) ?></span>
    <span class="text-muted small">Kelas <?= e($student['class_name'] ?? '-') ?></span>
    <span class="text-muted small">Wali Kelas: <?= e($student['homeroom_name'] ?? '-') ?></span>
</div>

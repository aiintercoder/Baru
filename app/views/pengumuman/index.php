<?php $me = current_user(); ?>
<div class="page-header">
    <h1><i class="bi bi-megaphone"></i> Pengumuman</h1>
    <?php if ($canEdit): ?>
        <a class="btn btn-primary" href="<?= url('pengumuman/form') ?>"><i class="bi bi-plus-lg"></i> Buat Pengumuman</a>
    <?php endif; ?>
</div>
<?php foreach ($items as $a): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h2 class="h5 mb-1"><?= e($a['title']) ?></h2>
                    <div class="small text-muted mb-2">
                        <?= tanggal($a['created_at'], true) ?> · oleh <?= e($a['author'] ?? '-') ?>
                        · <span class="badge text-bg-light border"><?= e(AUDIENCES[$a['audience']] ?? $a['audience']) ?></span>
                    </div>
                </div>
                <?php if ($canEdit && can_edit_announcement($me, $a)): ?>
                    <div class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= url('pengumuman/form', ['id' => $a['id']]) ?>"><i class="bi bi-pencil"></i></a>
                        <?= delete_button('pengumuman/hapus', (int) $a['id'], 'Hapus pengumuman ini?') ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="announcement-body"><?= e($a['body']) ?></div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$items): ?><div class="card"><div class="card-body text-muted">Belum ada pengumuman.</div></div><?php endif; ?>

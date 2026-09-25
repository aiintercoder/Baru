<div class="page-header"><h1><i class="bi bi-person-badge"></i> Data Guru</h1></div>
<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Nama</th><th>NIP</th><th>Peran</th><th>Wali Kelas</th><th>Mengampu</th><th>Kontak</th></tr></thead>
        <tbody>
        <?php foreach ($teachers as $t): ?>
            <tr>
                <td class="fw-semibold"><?= e($t['name']) ?><?= (int) $t['active'] ? '' : ' <span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                <td><?= e($t['nip'] ?? '-') ?></td>
                <td><?= e(role_label($t['role'])) ?></td>
                <td><?= e($t['homeroom_class'] ?? '-') ?></td>
                <td class="small"><?= e(implode(', ', $subjects[$t['id']] ?? []) ?: '-') ?></td>
                <td class="small"><?= e($t['phone'] ?? '') ?><?= $t['email'] ? '<br>' . e($t['email']) : '' ?></td>
            </tr>
        <?php endforeach; if (!$teachers) echo empty_row(6); ?>
        </tbody>
    </table>
</div></div>

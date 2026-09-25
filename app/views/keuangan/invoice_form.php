<div class="page-header"><h1><i class="bi bi-receipt"></i> Buat Tagihan</h1></div>
<div class="card" style="max-width: 760px"><div class="card-body">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Ditagihkan kepada</label>
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="target" id="tStudent" value="student" checked>
                <label class="btn btn-outline-primary" for="tStudent">Satu Siswa</label>
                <input type="radio" class="btn-check" name="target" id="tClass" value="class">
                <label class="btn btn-outline-primary" for="tClass">Satu Kelas</label>
                <input type="radio" class="btn-check" name="target" id="tAll" value="all">
                <label class="btn btn-outline-primary" for="tAll">Semua Siswa Aktif</label>
            </div></div>
        <div class="mb-3 target target-student"><label class="form-label">Siswa</label>
            <select class="form-select" name="student_id">
                <?php foreach ($students as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e(($s['class_name'] ?? '-') . ' · ' . $s['name'] . ' (' . $s['nis'] . ')') ?></option><?php endforeach; ?>
            </select></div>
        <div class="mb-3 target target-class d-none"><label class="form-label">Kelas</label>
            <select class="form-select" name="class_id">
                <?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="mb-3"><label class="form-label">Nama Tagihan</label>
            <input class="form-control" name="title" maxlength="150" placeholder="mis. SPP Oktober 2026" required></div>
        <div class="row g-2 mb-3">
            <div class="col-md-6"><label class="form-label">Jumlah (Rp)</label>
                <input class="form-control" name="amount" inputmode="numeric" placeholder="350000" required></div>
            <div class="col-md-6"><label class="form-label">Jatuh Tempo</label>
                <input class="form-control" type="date" name="due_date"></div>
        </div>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Buat Tagihan</button>
        <a class="btn btn-link" href="<?= url('keuangan/tagihan') ?>">Batal</a>
    </form>
</div></div>
<script>
document.querySelectorAll('input[name=target]').forEach(r => r.addEventListener('change', () => {
    document.querySelectorAll('.target').forEach(el => el.classList.add('d-none'));
    document.querySelector('.target-' + r.value)?.classList.remove('d-none');
}));
</script>

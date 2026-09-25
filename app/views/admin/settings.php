<div class="page-header"><h1><i class="bi bi-gear"></i> Pengaturan Sekolah</h1></div>
<div class="card" style="max-width: 760px"><div class="card-body">
    <form method="post" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-12"><label class="form-label">Nama Sekolah</label>
            <input class="form-control" name="school_name" value="<?= e($values['school_name']) ?>" required></div>
        <div class="col-12"><label class="form-label">Alamat</label>
            <input class="form-control" name="school_address" value="<?= e($values['school_address']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Telepon</label>
            <input class="form-control" name="school_phone" value="<?= e($values['school_phone']) ?>"></div>
        <div class="col-md-6"></div>
        <div class="col-md-6"><label class="form-label">Nama Kepala Sekolah</label>
            <input class="form-control" name="principal_name" value="<?= e($values['principal_name']) ?>"></div>
        <div class="col-md-6"><label class="form-label">NIP Kepala Sekolah</label>
            <input class="form-control" name="principal_nip" value="<?= e($values['principal_nip']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Tahun Ajaran Aktif</label>
            <input class="form-control" name="academic_year" value="<?= e($values['academic_year']) ?>" pattern="\d{4}/\d{4}" placeholder="2026/2027" required></div>
        <div class="col-md-6"><label class="form-label">Semester Aktif</label>
            <select class="form-select" name="semester">
                <option<?= selected('Ganjil', $values['semester']) ?>>Ganjil</option>
                <option<?= selected('Genap', $values['semester']) ?>>Genap</option>
            </select></div>
        <div class="col-12">
            <div class="form-text mb-2">Tahun ajaran & semester aktif menentukan periode input nilai dan rapor.</div>
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan Pengaturan</button>
        </div>
    </form>
</div></div>

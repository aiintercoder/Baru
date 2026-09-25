<div class="page-header"><h1><?= $isNew ? 'Catat' : 'Ubah' ?> Surat</h1></div>
<div class="card" style="max-width: 760px"><div class="card-body">
    <form method="post" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-4"><label class="form-label">Jenis</label>
            <select class="form-select" name="direction">
                <option value="masuk"<?= selected('masuk', $letter['direction'] ?? param('direction', 'masuk')) ?>>Surat Masuk</option>
                <option value="keluar"<?= selected('keluar', $letter['direction'] ?? param('direction')) ?>>Surat Keluar</option>
            </select></div>
        <div class="col-md-4"><label class="form-label">Nomor Surat</label>
            <input class="form-control" name="letter_number" value="<?= e($letter['letter_number'] ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Tanggal Surat</label>
            <input class="form-control" type="date" name="letter_date" value="<?= e($letter['letter_date'] ?? date('Y-m-d')) ?>" required></div>
        <div class="col-12"><label class="form-label">Pengirim / Penerima</label>
            <input class="form-control" name="party" value="<?= e($letter['party'] ?? '') ?>" required></div>
        <div class="col-12"><label class="form-label">Perihal</label>
            <input class="form-control" name="subject" value="<?= e($letter['subject'] ?? '') ?>" required></div>
        <div class="col-12"><label class="form-label">Keterangan / Disposisi</label>
            <textarea class="form-control" name="note" rows="3"><?= e($letter['note'] ?? '') ?></textarea></div>
        <div class="col-12">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a class="btn btn-link" href="<?= url('surat') ?>">Batal</a>
        </div>
    </form>
</div></div>

<?php
$scored = array_filter(array_column($grades, 'score'), fn($v) => $v !== null);
$avg = $scored ? array_sum($scored) / count($scored) : null;
$me = current_user();
?>
<div class="page-header d-print-none">
    <h1><i class="bi bi-award"></i> Nilai & Rapor</h1>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="r" value="rapor">
            <?php if ($me['role'] !== 'siswa'): ?><input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>"><?php endif; ?>
            <select class="form-select form-select-sm" name="year" style="min-width: 8rem" onchange="this.form.submit()">
                <?php $years = array_unique(array_merge([period()['year'], $year], array_column($periods, 'academic_year')));
                foreach ($years as $y): ?><option<?= selected($y, $year) ?>><?= e($y) ?></option><?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" name="semester" onchange="this.form.submit()">
                <option<?= selected('Ganjil', $semester) ?>>Ganjil</option><option<?= selected('Genap', $semester) ?>>Genap</option>
            </select>
        </form>
        <button class="btn btn-sm btn-outline-secondary text-nowrap" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="text-center border-bottom pb-3 mb-3">
            <div class="h5 fw-bold mb-0"><?= e(setting('school_name', config('app_name'))) ?></div>
            <div class="small text-muted"><?= e(setting('school_address')) ?></div>
            <div class="h6 mt-3 mb-0">LAPORAN HASIL BELAJAR PESERTA DIDIK</div>
        </div>
        <div class="row small mb-3">
            <div class="col-sm-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:40%">Nama</td><td>: <strong><?= e($student['name']) ?></strong></td></tr>
                    <tr><td>NIS</td><td>: <?= e($student['nis']) ?></td></tr>
                </table>
            </div>
            <div class="col-sm-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:40%">Kelas</td><td>: <?= e($student['class_name'] ?? '-') ?></td></tr>
                    <tr><td>Tahun Ajaran / Semester</td><td>: <?= e($year) ?> / <?= e($semester) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light text-center"><tr><th>No</th><th class="text-start">Mata Pelajaran</th><th>KKM</th><th>Tugas</th><th>UTS</th><th>UAS</th><th>Nilai Akhir</th><th>Predikat</th><th>Ket.</th></tr></thead>
            <tbody>
            <?php foreach ($grades as $i => $g): ?>
                <tr class="text-center">
                    <td><?= $i + 1 ?></td>
                    <td class="text-start"><?= e($g['subject']) ?><div class="small text-muted d-print-none"><?= e($g['teacher']) ?></div></td>
                    <td><?= (int) $g['kkm'] ?></td>
                    <td><?= fmt_score($g['task_score']) ?></td><td><?= fmt_score($g['mid_score']) ?></td><td><?= fmt_score($g['final_score']) ?></td>
                    <td class="fw-bold <?= $g['score'] !== null && $g['score'] < $g['kkm'] ? 'text-danger' : '' ?>"><?= fmt_score($g['score']) ?></td>
                    <td><?= predicate($g['score']) ?></td>
                    <td class="small"><?= $g['score'] === null ? '-' : ($g['score'] >= $g['kkm'] ? 'Tuntas' : 'Belum Tuntas') ?></td>
                </tr>
            <?php endforeach; if (!$grades) echo empty_row(9, 'Belum ada mata pelajaran untuk kelas ini.'); ?>
            </tbody>
            <?php if ($avg !== null): ?>
            <tfoot><tr class="table-light text-center fw-bold"><td colspan="6" class="text-end">Rata-rata</td><td><?= fmt_score(round($avg, 2)) ?></td><td><?= predicate($avg) ?></td><td></td></tr></tfoot>
            <?php endif; ?>
        </table>
        </div>

        <div class="row g-3">
            <div class="col-md-5">
                <table class="table table-bordered table-sm small">
                    <thead class="table-light"><tr><th colspan="2">Ketidakhadiran</th></tr></thead>
                    <tr><td>Sakit</td><td><?= (int) $attendance['S'] ?> hari</td></tr>
                    <tr><td>Izin</td><td><?= (int) $attendance['I'] ?> hari</td></tr>
                    <tr><td>Tanpa Keterangan</td><td><?= (int) $attendance['A'] ?> hari</td></tr>
                </table>
            </div>
            <div class="col-md-7">
                <div class="border rounded p-2 small h-100">
                    <div class="fw-semibold mb-1">Catatan Wali Kelas</div>
                    <div class="announcement-body"><?= $note ? e($note) : '<span class="text-muted">-</span>' ?></div>
                </div>
            </div>
        </div>

        <div class="row text-center small mt-5">
            <div class="col-4">Orang Tua/Wali<br><br><br><br>( ................................ )</div>
            <div class="col-4">Wali Kelas<br><br><br><br><strong><?= e($student['homeroom_name'] ?? '................................') ?></strong><br>NIP <?= e($student['homeroom_nip'] ?? '-') ?></div>
            <div class="col-4">Kepala Sekolah<br><br><br><br><strong><?= e(setting('principal_name', '................................')) ?></strong><br>NIP <?= e(setting('principal_nip', '-')) ?></div>
        </div>
    </div>
</div>
<p class="small text-muted mt-2 d-print-none">Predikat: A ≥ 90 · B ≥ 80 · C ≥ 70 · D &lt; 70</p>

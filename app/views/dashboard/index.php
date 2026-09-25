<?php
$role = $me['role'];
$countsFrom = function (array $rows): array {
    $c = array_fill_keys(array_keys(ATTENDANCE_STATUS), 0);
    foreach ($rows as $r) { $c[$r['status']] = (int) $r['n']; }
    return $c;
};
?>
<div class="page-header">
    <div>
        <h1>Selamat datang, <?= e($me['name']) ?> 👋</h1>
        <div class="text-muted small"><?= tanggal(date('Y-m-d'), true) ?> · <?= e(role_label($role)) ?></div>
    </div>
</div>

<?php if (in_array($role, ['admin', 'kepala_sekolah'], true)): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><?= stat_card('Siswa Aktif', $stats['students'], 'mortarboard', 'primary', url('data/siswa')) ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Guru', $stats['teachers'], 'person-badge', 'success', url('data/guru')) ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Kelas', $stats['classes'], 'door-open', 'warning') ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Mata Pelajaran', $stats['subjects'], 'book', 'info') ?></div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header bg-white fw-semibold">Kehadiran Hari Ini</div>
                <div class="card-body">
                    <?php $c = $countsFrom($todayAttendance); $total = array_sum($c); ?>
                    <?php if ($total === 0): ?>
                        <p class="text-muted mb-0">Belum ada absensi yang diinput hari ini.</p>
                    <?php else: foreach (ATTENDANCE_STATUS as $k => $label): $pct = round($c[$k] / $total * 100); ?>
                        <div class="d-flex justify-content-between small"><span><?= e($label) ?></span><span><?= $c[$k] ?> (<?= $pct ?>%)</span></div>
                        <div class="progress mb-2" style="height:8px"><div class="progress-bar bg-<?= ['H'=>'success','S'=>'info','I'=>'warning','A'=>'danger'][$k] ?>" style="width: <?= $pct ?>%"></div></div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-header bg-white fw-semibold">Ringkasan Keuangan</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>Total Tagihan</td><td class="text-end fw-semibold"><?= rupiah($finance['billed']) ?></td></tr>
                        <tr><td>Total Terbayar</td><td class="text-end text-success fw-semibold"><?= rupiah($finance['paid']) ?></td></tr>
                        <tr><td>Tunggakan</td><td class="text-end text-danger fw-semibold"><?= rupiah($finance['outstanding']) ?></td></tr>
                        <tr><td>Penerimaan Bulan Ini</td><td class="text-end"><?= rupiah($finance['month']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php if ($role === 'admin'): ?>
    <div class="card mb-4"><div class="card-header bg-white fw-semibold">Pengguna per Peran</div>
        <div class="card-body d-flex flex-wrap gap-2">
            <?php foreach ($roleCounts as $rc): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= url('admin/pengguna', ['role' => $rc['role']]) ?>">
                    <?= e(role_label($rc['role'])) ?> <span class="badge text-bg-secondary"><?= (int) $rc['n'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php elseif ($role === 'tata_usaha'): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><?= stat_card('Total Tagihan', rupiah($finance['billed']), 'receipt', 'primary') ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Terbayar', rupiah($finance['paid']), 'check-circle', 'success') ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Tunggakan', rupiah($finance['outstanding']), 'exclamation-triangle', 'danger', url('keuangan/tagihan', ['status' => 'belum'])) ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Arsip Surat', $letterCount, 'envelope-paper', 'info', url('surat')) ?></div>
    </div>
    <div class="card mb-4"><div class="card-header bg-white fw-semibold d-flex justify-content-between">
        Pembayaran Terbaru <a href="<?= url('keuangan/tagihan') ?>" class="small">Lihat semua</a></div>
        <div class="table-responsive"><table class="table table-hover mb-0">
            <thead><tr><th>Tanggal</th><th>Siswa</th><th>Tagihan</th><th class="text-end">Jumlah</th></tr></thead>
            <tbody>
            <?php foreach ($recentPayments as $p): ?>
                <tr><td><?= tanggal($p['paid_at']) ?></td><td><?= e($p['student_name']) ?></td><td><?= e($p['title']) ?></td><td class="text-end"><?= rupiah($p['amount']) ?></td></tr>
            <?php endforeach; if (!$recentPayments) echo empty_row(4); ?>
            </tbody>
        </table></div>
    </div>

<?php elseif (in_array($role, TEACHER_ROLES, true)): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><?= stat_card('Kelas Diajar', $classCount, 'door-open', 'primary') ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Mapel Diampu', $assignmentCount, 'book', 'success', url('guru/nilai')) ?></div>
        <?php if (!empty($homeroom)): $hc = $countsFrom($homeroomToday); ?>
            <div class="col-6 col-xl-3"><?= stat_card('Siswa Kelas ' . $homeroom['name'], $homeroomCount, 'people', 'warning', url('walikelas/kelas')) ?></div>
            <div class="col-6 col-xl-3"><?= stat_card('Hadir Hari Ini', $hc['H'] . ' / ' . $homeroomCount, 'calendar-check', 'info', url('guru/absensi', ['class_id' => $homeroom['id']])) ?></div>
        <?php endif; ?>
    </div>
    <div class="card mb-4"><div class="card-header bg-white fw-semibold">Jadwal Mengajar Hari Ini</div>
        <div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Jam</th><th>Kelas</th><th>Mata Pelajaran</th><th>Ruang</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($todaySchedule as $s): ?>
                <tr><td><?= e($s['start_time']) ?>–<?= e($s['end_time']) ?></td><td><?= e($s['class_name']) ?></td>
                    <td><?= e($s['subject']) ?></td><td><?= e($s['room']) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('guru/nilai', ['assignment_id' => $s['assignment_id']]) ?>">Nilai</a></td></tr>
            <?php endforeach; if (!$todaySchedule) echo empty_row(5, 'Tidak ada jadwal mengajar hari ini.'); ?>
            </tbody>
        </table></div>
    </div>

<?php elseif (in_array($role, ['siswa', 'orang_tua'], true)): ?>
    <div class="alert alert-light border mb-3">
        <i class="bi bi-person-vcard"></i> <strong><?= e($student['name']) ?></strong> · NIS <?= e($student['nis']) ?> ·
        Kelas <?= e($student['class_name'] ?? '-') ?> · Wali Kelas: <?= e($student['homeroom_name'] ?? '-') ?>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><?= stat_card('Rata-rata Nilai', $average === null ? '-' : fmt_score($average), 'award', 'primary', url('rapor')) ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Hadir (semester ini)', $attendance['H'], 'calendar-check', 'success', url('siswa/absensi')) ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Sakit / Izin / Alpa', $attendance['S'] . ' / ' . $attendance['I'] . ' / ' . $attendance['A'], 'calendar-x', 'warning', url('siswa/absensi')) ?></div>
        <div class="col-6 col-xl-3"><?= stat_card('Tunggakan', rupiah($outstanding), 'receipt', $outstanding > 0 ? 'danger' : 'success', url('siswa/tagihan')) ?></div>
    </div>
    <div class="card mb-4"><div class="card-header bg-white fw-semibold">Jadwal Hari Ini</div>
        <div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Jam</th><th>Mata Pelajaran</th><th>Guru</th><th>Ruang</th></tr></thead>
            <tbody>
            <?php foreach ($todaySchedule as $s): ?>
                <tr><td><?= e($s['start_time']) ?>–<?= e($s['end_time']) ?></td><td><?= e($s['subject']) ?></td><td><?= e($s['teacher']) ?></td><td><?= e($s['room']) ?></td></tr>
            <?php endforeach; if (!$todaySchedule) echo empty_row(4, 'Tidak ada jadwal pelajaran hari ini.'); ?>
            </tbody>
        </table></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-megaphone"></i> Pengumuman Terbaru</span>
        <a href="<?= url('pengumuman') ?>" class="small">Lihat semua</a>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach ($announcements as $a): ?>
            <li class="list-group-item">
                <div class="fw-semibold"><?= e($a['title']) ?></div>
                <div class="small text-muted"><?= tanggal($a['created_at']) ?> · <?= e($a['author'] ?? '-') ?></div>
                <div class="small mt-1 announcement-body"><?= e(mb_strimwidth($a['body'], 0, 220, '…')) ?></div>
            </li>
        <?php endforeach; ?>
        <?php if (!$announcements): ?><li class="list-group-item text-muted">Belum ada pengumuman.</li><?php endif; ?>
    </ul>
</div>

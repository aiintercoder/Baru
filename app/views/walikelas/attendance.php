<?php $colors = ['H' => 'success', 'S' => 'info', 'I' => 'warning', 'A' => 'danger']; ?>
<div class="page-header">
    <h1><i class="bi bi-calendar-check"></i> Rekap Absensi Kelas <?= e($class['name']) ?></h1>
    <form method="get" class="d-flex gap-2 d-print-none">
        <input type="hidden" name="r" value="walikelas/rekap-absensi">
        <input class="form-control" type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
    </form>
</div>
<div class="card"><div class="table-responsive">
    <table class="table table-bordered table-sm mb-0 small text-center">
        <thead class="table-light"><tr><th class="text-start">Nama</th>
            <?php for ($d = 1; $d <= $days; $d++): ?><th><?= $d ?></th><?php endfor; ?>
            <?php foreach (ATTENDANCE_STATUS as $k => $_): ?><th class="text-<?= $colors[$k] ?>"><?= $k ?></th><?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($students as $s): $tot = array_fill_keys(array_keys(ATTENDANCE_STATUS), 0); ?>
            <tr><td class="text-start text-nowrap"><?= e($s['name']) ?></td>
                <?php for ($d = 1; $d <= $days; $d++): $st = $matrix[$s['id']][$d] ?? null; if ($st) $tot[$st]++; ?>
                    <td class="<?= $st ? 'text-' . $colors[$st] . ' fw-semibold' : 'text-muted' ?>"><?= $st ?? '·' ?></td>
                <?php endfor; ?>
                <?php foreach ($tot as $k => $n): ?><td class="fw-semibold"><?= $n ?></td><?php endforeach; ?>
            </tr>
        <?php endforeach; if (!$students) echo empty_row($days + 5); ?>
        </tbody>
    </table>
</div></div>

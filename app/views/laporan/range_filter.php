<form method="get" class="d-flex flex-wrap gap-2 align-items-center d-print-none">
    <input type="hidden" name="r" value="<?= e(param('r')) ?>">
    <input class="form-control form-control-sm w-auto" type="date" name="from" value="<?= e($from) ?>">
    <span class="small">s.d.</span>
    <input class="form-control form-control-sm w-auto" type="date" name="to" value="<?= e($to) ?>">
    <button class="btn btn-sm btn-outline-primary">Terapkan</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button>
</form>

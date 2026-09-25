<?php
/** @var string $content @var string $pageTitle */
$me = current_user();
$currentRoute = trim(param('r'), '/');
$schoolName = setting('school_name', config('app_name'));
$p = period();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ? $pageTitle . ' · ' : '') ?><?= e($schoolName) ?></title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<?php if ($me): ?>
<nav class="navbar navbar-dark bg-primary sticky-top shadow-sm d-print-none">
    <div class="container-fluid">
        <button class="navbar-toggler d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand fw-semibold me-auto" href="<?= url() ?>"><i class="bi bi-mortarboard-fill"></i> <?= e($schoolName) ?></a>
        <span class="navbar-text text-white-50 small d-none d-md-inline me-3">
            TA <?= e($p['year']) ?> · Semester <?= e($p['semester']) ?>
        </span>
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i> <span class="d-none d-sm-inline"><?= e($me['name']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?= e(role_label($me['role'])) ?></span></li>
                <li><a class="dropdown-item" href="<?= url('profil') ?>"><i class="bi bi-person"></i> Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= url('logout') ?>"><?= csrf_field() ?>
                        <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start sidebar border-end bg-body d-print-none" tabindex="-1" id="sidebar">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
        </div>
        <div class="offcanvas-body d-block p-2">
            <div class="px-2 py-2 mb-2 small">
                <span class="badge text-bg-primary"><?= e(role_label($me['role'])) ?></span>
            </div>
            <?php foreach (menu_for($me['role']) as $section => $items): ?>
                <div class="menu-section"><?= e($section) ?></div>
                <ul class="nav nav-pills flex-column mb-2">
                    <?php foreach ($items as [$label, $route, $icon]):
                        $active = $currentRoute === $route || ($route !== '' && str_starts_with($currentRoute, $route . '/')); ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= url($route) ?>">
                                <i class="bi bi-<?= e($icon) ?>"></i> <?= e($label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
    </aside>
    <main class="flex-grow-1 p-3 p-md-4 main-content">
        <?php if ($me['role'] === 'orang_tua' && ($children = parent_children((int) $me['id'])) && count($children) > 1): ?>
            <form class="d-flex align-items-center gap-2 mb-3 d-print-none" method="get">
                <input type="hidden" name="r" value="<?= e($currentRoute) ?>">
                <label class="small text-muted">Lihat data anak:</label>
                <select name="student_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <?php foreach ($children as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $_SESSION['child_id'] ?? $children[0]['id']) ?>>
                            <?= e($c['name']) ?> (<?= e($c['class_name'] ?? '-') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <?php foreach (take_flashes() as [$type, $msg]): ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show d-print-none" role="alert">
                <?= e($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div>
<?php else: ?>
    <?= $content ?>
<?php endif; ?>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', ev => {
    if (!confirm(f.dataset.confirm)) ev.preventDefault();
}));
</script>
</body>
</html>

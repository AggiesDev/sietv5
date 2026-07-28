<?php
if (!ob_get_level()) {
    ob_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = $pageTitle ?? 'Admin';
$flashes = consume_flash();
$adminPages = [
    'index.php' => ['Dashboard', 'bi-speedometer2'],
    'search.php' => ['Search', 'bi-search'],
    'news.php' => ['News', 'bi-newspaper'],
    'events.php' => ['Events', 'bi-calendar-event'],
    'partners.php' => ['Partners', 'bi-diagram-3'],
    'members.php' => ['Members', 'bi-people'],
    'council.php' => ['Council', 'bi-person-badge'],
    'banners.php' => ['Banners', 'bi-images'],
    'pages.php' => ['Pages', 'bi-file-earmark-richtext'],
    'nav.php' => ['Navigation', 'bi-list-nested'],
    'import.php' => ['Import Data', 'bi-file-earmark-arrow-up'],
    'messages.php' => ['Messages', 'bi-envelope'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | SIET Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= SITE_URL ?>/assets/css/custom.css" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
  <div class="admin-sidebar d-none d-lg-block p-3">
    <h4 class="text-gold mb-4">SIET Admin</h4>
    <?php foreach ($adminPages as $file => $meta): ?><a class="<?= active_class($file) ?>" href="<?= SITE_URL ?>/admin/<?= $file ?>"><i class="bi <?= $meta[1] ?> me-2"></i><?= $meta[0] ?></a><?php endforeach; ?>
  </div>
  <div class="admin-main">
    <nav class="navbar navbar-light bg-white border-bottom px-3">
      <button class="btn btn-outline-primary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#adminMenu"><i class="bi bi-list"></i></button>
      <span class="fw-bold"><?= e($pageTitle) ?></span>
      <form class="d-none d-md-flex ms-auto me-3" method="get" action="<?= SITE_URL ?>/admin/search.php" role="search">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= basename($_SERVER['SCRIPT_NAME']) === 'search.php' ? e($_GET['q'] ?? '') : '' ?>" placeholder="Search admin">
      </form>
      <div><span class="me-3 small"><?= e(current_user()['name']) ?></span><a class="btn btn-sm btn-outline-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i></a></div>
    </nav>
    <div class="offcanvas offcanvas-start admin-sidebar" id="adminMenu">
      <div class="offcanvas-header"><h5 class="text-gold">SIET Admin</h5><button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
      <div class="offcanvas-body"><?php foreach ($adminPages as $file => $meta): ?><a class="<?= active_class($file) ?>" href="<?= SITE_URL ?>/admin/<?= $file ?>"><i class="bi <?= $meta[1] ?> me-2"></i><?= $meta[0] ?></a><?php endforeach; ?></div>
    </div>
    <?php if ($flashes): ?><div class="toast-container position-fixed top-0 end-0 p-3"><?php foreach ($flashes as $flash): ?><div class="toast show text-bg-<?= e($flash['type']) ?> border-0"><div class="d-flex"><div class="toast-body"><?= e($flash['message']) ?></div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div><?php endforeach; ?></div><?php endif; ?>
    <main class="p-3 p-lg-4">

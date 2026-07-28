<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/_header.php';
$stats = [
    ['Members', count_rows($pdo, 'users', "role='member'"), 'bi-people'],
    ['Events', count_rows($pdo, 'events'), 'bi-calendar-event'],
    ['News', count_rows($pdo, 'news'), 'bi-newspaper'],
    ['Unread Messages', count_rows($pdo, 'contact_messages', 'is_read=0'), 'bi-envelope-exclamation'],
    ['Custom Pages', table_exists($pdo, 'site_pages') ? count_rows($pdo, 'site_pages') : 0, 'bi-file-earmark-richtext'],
    ['Active Banners', table_exists($pdo, 'site_banners') ? count_rows($pdo, 'site_banners', 'is_active=1') : 0, 'bi-images'],
];
$uploadReady = is_dir(UPLOAD_PATH) && is_writable(UPLOAD_PATH);
?>
<div class="row g-4">
  <?php foreach ($stats as $stat): ?>
    <div class="col-md-6 col-xl-3"><div class="card card-body"><i class="bi <?= $stat[2] ?> fs-2 text-gold"></i><h2><?= $stat[1] ?></h2><p class="mb-0"><?= e($stat[0]) ?></p></div></div>
  <?php endforeach; ?>
</div>
<div class="card card-body mt-4">
  <h5>Admin Notes</h5>
  <p>Use the sidebar to manage placeholder portal content, applications, and inbox messages.</p>
  <div class="alert <?= $uploadReady ? 'alert-success' : 'alert-danger' ?> mb-0">
    <strong>Upload storage:</strong> <?= $uploadReady ? 'Ready' : 'Not writable' ?><br>
    <span class="small"><?= e(UPLOAD_PATH) ?></span>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>

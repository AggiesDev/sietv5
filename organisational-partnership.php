<?php
$pageTitle = 'Organisation Partners';
require_once __DIR__ . '/includes/header.php';
$partners = $pdo->query("SELECT * FROM partners WHERE status='active' ORDER BY type, name")->fetchAll();
page_intro('Our Organisation Partners', 'Placeholder partner cards matching the reference card rhythm.');
?>
<div class="container py-5"><div class="row g-4">
<?php foreach ($partners as $partner): ?>
  <div class="col-md-6 col-lg-4"><div class="card card-hover h-100">
    <img src="<?= e(media_url($partner['logo']) ?: 'https://placehold.co/400x300?text=Partner') ?>" class="card-img-top" alt="">
    <div class="card-body"><span class="badge-soft mb-2"><?= e($partner['type']) ?></span><h5 class="fw-bold"><?= e($partner['name']) ?></h5><p class="text-muted"><?= e($partner['description']) ?></p></div>
  </div></div>
<?php endforeach; ?>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

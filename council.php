<?php
$pageTitle = 'Council';
require_once __DIR__ . '/includes/header.php';
$members = $pdo->query('SELECT * FROM council ORDER BY display_order, name')->fetchAll();
page_intro('Council', 'Placeholder council members managed from the admin panel.');
?>
<div class="container py-5"><div class="row g-4">
<?php foreach ($members as $member): ?>
  <div class="col-md-6 col-lg-4"><div class="card h-100">
    <img src="<?= e(media_url($member['image']) ?: 'https://placehold.co/400x300?text=Council') ?>" class="card-img-top" alt="">
    <div class="card-body"><h5><?= e($member['name']) ?></h5><p class="text-gold fw-bold mb-1"><?= e($member['position']) ?></p><p class="small text-muted"><?= e($member['period']) ?></p><p><?= e($member['bio']) ?></p></div>
  </div></div>
<?php endforeach; ?>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

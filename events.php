<?php
$pageTitle = 'Events';
require_once __DIR__ . '/includes/header.php';
page_intro('Events', 'Upcoming, ongoing, and completed professional programmes.');
$pager = paginate($pdo, 'events', '1=1', [], 6);
$stmt = $pdo->prepare("SELECT * FROM events ORDER BY event_date DESC LIMIT {$pager['perPage']} OFFSET {$pager['offset']}");
$stmt->execute();
$items = $stmt->fetchAll();
?>
<div class="container py-5">
  <div class="row g-4">
    <?php foreach ($items as $item): ?>
      <div class="col-md-6 col-lg-4"><div class="card h-100">
        <img src="<?= e(media_url($item['image']) ?: 'https://placehold.co/400x300?text=Event') ?>" class="card-img-top" alt="">
        <div class="card-body">
          <span class="badge text-bg-primary mb-2"><?= e($item['status']) ?></span>
          <h5><?= e($item['title']) ?></h5>
          <p class="mb-1"><i class="bi bi-calendar me-2"></i><?= e($item['event_date']) ?> <?= e(substr($item['event_time'], 0, 5)) ?></p>
          <p><i class="bi bi-geo-alt me-2"></i><?= e($item['location']) ?></p>
          <a href="<?= SITE_URL ?>/event-detail.php?slug=<?= e($item['slug']) ?>" class="btn btn-sm btn-primary">Details</a>
        </div>
      </div></div>
    <?php endforeach; ?>
  </div>
  <?php if ($pager['pages'] > 1): ?><nav class="mt-4"><ul class="pagination"><?php for ($i = 1; $i <= $pager['pages']; $i++): ?><li class="page-item <?= $i === $pager['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
$pageTitle = 'News';
require_once __DIR__ . '/includes/header.php';
page_intro('News', 'Published placeholder updates from the SIET portal.');
$pager = paginate($pdo, 'news', "status = 'published'", [], 6);
$stmt = $pdo->prepare("SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT {$pager['perPage']} OFFSET {$pager['offset']}");
$stmt->execute();
$items = $stmt->fetchAll();
?>
<div class="container py-5">
  <div class="row g-4">
    <?php foreach ($items as $item): ?>
      <div class="col-md-6 col-lg-4"><div class="card h-100">
        <img src="<?= e(media_url($item['image']) ?: 'https://placehold.co/400x300?text=News') ?>" class="card-img-top" alt="">
        <div class="card-body"><h5><?= e($item['title']) ?></h5><p><?= e($item['excerpt']) ?></p><a href="<?= SITE_URL ?>/news-detail.php?slug=<?= e($item['slug']) ?>" class="btn btn-sm btn-primary">Read</a></div>
      </div></div>
    <?php endforeach; ?>
  </div>
  <?php if ($pager['pages'] > 1): ?><nav class="mt-4"><ul class="pagination"><?php for ($i = 1; $i <= $pager['pages']; $i++): ?><li class="page-item <?= $i === $pager['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

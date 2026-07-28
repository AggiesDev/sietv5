<?php
$pageTitle = 'News Detail';
require_once __DIR__ . '/includes/header.php';
$stmt = $pdo->prepare("SELECT n.*, u.name AS author FROM news n LEFT JOIN users u ON u.id = n.created_by WHERE n.slug = ? AND n.status = 'published'");
$stmt->execute([sanitize($_GET['slug'] ?? '')]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); page_intro('News Not Found', 'The requested article is unavailable.'); require_once __DIR__ . '/includes/footer.php'; exit; }
page_intro($item['title'], 'Published ' . date('d M Y', strtotime($item['created_at'])));
?>
<article class="container py-5" style="max-width:900px">
  <img src="<?= e(media_url($item['image']) ?: 'https://placehold.co/900x500?text=News') ?>" class="img-fluid rounded mb-4" alt="">
  <p class="text-muted">By <?= e($item['author'] ?: 'SIET Admin') ?></p>
  <div class="card card-body"><p><?= nl2br(e($item['content'])) ?></p></div>
</article>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

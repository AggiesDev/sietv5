<?php
$pageTitle = 'Event Detail';
require_once __DIR__ . '/includes/header.php';
$stmt = $pdo->prepare('SELECT * FROM events WHERE slug = ?');
$stmt->execute([sanitize($_GET['slug'] ?? '')]);
$event = $stmt->fetch();
if (!$event) { http_response_code(404); page_intro('Event Not Found', 'The requested event is unavailable.'); require_once __DIR__ . '/includes/footer.php'; exit; }
page_intro($event['title'], e($event['location']));
?>
<article class="container py-5" style="max-width:900px">
  <img src="<?= e(media_url($event['image']) ?: 'https://placehold.co/900x500?text=Event') ?>" class="img-fluid rounded mb-4" alt="">
  <div class="card card-body">
    <div class="row g-3 mb-3">
      <div class="col-md-4"><strong>Date:</strong> <?= e($event['event_date']) ?></div>
      <div class="col-md-4"><strong>Time:</strong> <?= e(substr($event['event_time'], 0, 5)) ?></div>
      <div class="col-md-4"><strong>Capacity:</strong> <?= e((string) $event['max_participants']) ?></div>
    </div>
    <p><?= nl2br(e($event['description'])) ?></p>
  </div>
</article>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

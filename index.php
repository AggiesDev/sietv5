<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
$memberCount = count_rows($pdo, 'users', "role = 'member' AND membership_status = 'active'");
$eventCount = count_rows($pdo, 'events', "status IN ('upcoming','ongoing')");
$newsCount = count_rows($pdo, 'news', "status = 'published'");
$news = $pdo->query("SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
$events = $pdo->query("SELECT * FROM events WHERE status IN ('upcoming','ongoing') ORDER BY event_date ASC LIMIT 3")->fetchAll();
$homeBanners = [];
if (table_exists($pdo, 'site_banners')) {
    $homeBanners = $pdo->query("SELECT * FROM site_banners WHERE scope='home' AND is_active=1 ORDER BY sort_order, id DESC LIMIT 5")->fetchAll();
}
if (!$homeBanners) {
    $homeBanners = [
        ['image' => 'https://placehold.co/1600x520/0d6efd/ffffff?text=SIET+Professional+Recognition', 'title' => '', 'subtitle' => '', 'button_label' => '', 'button_url' => ''],
        ['image' => 'https://placehold.co/1600x520/06b6d4/ffffff?text=Engineering+Technology+Community', 'title' => '', 'subtitle' => '', 'button_label' => '', 'button_url' => ''],
        ['image' => 'https://placehold.co/1600x520/a855f7/ffffff?text=CPD+Events+and+Global+Network', 'title' => '', 'subtitle' => '', 'button_label' => '', 'button_url' => ''],
    ];
}
?>
<section class="hero-slider">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover" data-bs-touch="true">
    <div class="carousel-indicators">
      <?php foreach ($homeBanners as $index => $banner): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= $index + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
      <?php foreach ($homeBanners as $index => $banner): ?>
        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
          <img src="<?= e(media_url($banner['image'])) ?>" class="d-block w-100 hero-img" alt="Placeholder hero slide">
          <?php if (!empty($banner['title']) || !empty($banner['subtitle'])): ?>
            <div class="carousel-caption d-none d-md-block">
              <?php if (!empty($banner['title'])): ?><h1><?= e($banner['title']) ?></h1><?php endif; ?>
              <?php if (!empty($banner['subtitle'])): ?><p><?= e($banner['subtitle']) ?></p><?php endif; ?>
              <?php if (!empty($banner['button_label']) && !empty($banner['button_url'])): ?><a class="btn btn-primary rounded-pill" href="<?= e($banner['button_url']) ?>"><?= e($banner['button_label']) ?></a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Previous slide">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Next slide">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
    </button>
  </div>
</section>

<section class="py-5 section-soft text-center">
  <div class="container">
    <h2 class="section-title">Our Vision</h2>
    <p class="mb-4 text-muted">Placeholder vision statement for a professional engineering technology body.</p>
    <hr class="my-4">
    <h2 class="section-title">Our Mission</h2>
    <p class="mb-4 text-muted">Placeholder mission statement describing member development, recognition, partnerships, and continuing professional practice.</p>
    <hr class="my-4">
    <h2 class="section-title">Our Core Values</h2>
    <div class="row mt-4 g-4">
      <div class="col-12 col-md-6 col-lg-3"><div class="value-card"><div class="value-letter letter-s">S</div><div class="fw-bold mb-2">Self-Discipline</div><p class="text-muted mb-0">Placeholder value text</p></div></div>
      <div class="col-12 col-md-6 col-lg-3"><div class="value-card"><div class="value-letter letter-i">I</div><div class="fw-bold mb-2">Impartiality</div><p class="text-muted mb-0">Placeholder value text</p></div></div>
      <div class="col-12 col-md-6 col-lg-3"><div class="value-card"><div class="value-letter letter-e">E</div><div class="fw-bold mb-2">Excellence</div><p class="text-muted mb-0">Placeholder value text</p></div></div>
      <div class="col-12 col-md-6 col-lg-3"><div class="value-card"><div class="value-letter letter-t">T</div><div class="fw-bold mb-2">Trustworthiness</div><p class="text-muted mb-0">Placeholder value text</p></div></div>
    </div>
  </div>
</section>

<!-- <section class="container py-5">
  <div class="row g-4">
    <div class="col-md-4"><div class="stat-card p-4 card-hover"><i class="bi bi-people fs-2 text-brand"></i><h2 class="fw-black mt-2"><?= $memberCount ?></h2><p class="mb-0 text-muted">Active members</p></div></div>
    <div class="col-md-4"><div class="stat-card p-4 card-hover"><i class="bi bi-calendar2-week fs-2 text-brand"></i><h2 class="fw-black mt-2"><?= $eventCount ?></h2><p class="mb-0 text-muted">Current events</p></div></div>
    <div class="col-md-4"><div class="stat-card p-4 card-hover"><i class="bi bi-newspaper fs-2 text-brand"></i><h2 class="fw-black mt-2"><?= $newsCount ?></h2><p class="mb-0 text-muted">Published news</p></div></div>
  </div>
</section> -->

<section class="container pb-5">
  <div class="d-flex align-items-end justify-content-between mb-3">
    <div><span class="pill mb-2">Updates</span><h2 class="section-title mb-0">News</h2></div>
    <a href="<?= SITE_URL ?>/news.php" class="btn btn-outline-primary rounded-pill">View All</a>
  </div>
  <div class="row g-4">
    <?php foreach ($news as $item): ?>
      <div class="col-md-6 col-lg-4"><div class="card card-hover h-100">
        <img src="<?= e(media_url($item['image']) ?: 'https://placehold.co/400x300?text=News') ?>" class="card-img-top" alt="">
        <div class="card-body"><h5 class="fw-bold"><?= e($item['title']) ?></h5><p class="text-muted"><?= e($item['excerpt']) ?></p><a class="btn btn-primary rounded-pill btn-sm" href="<?= SITE_URL ?>/news-detail.php?slug=<?= e($item['slug']) ?>">Read More</a></div>
      </div></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="py-5 section-soft">
  <div class="container">
    <div class="d-flex align-items-end justify-content-between mb-3">
      <div><span class="pill mb-2">Programmes</span><h2 class="section-title mb-0">Events</h2></div>
      <a href="<?= SITE_URL ?>/events.php" class="btn btn-outline-primary rounded-pill">View All</a>
    </div>
    <div class="row g-4">
      <?php foreach ($events as $event): ?>
        <div class="col-md-6 col-lg-4"><div class="card card-hover h-100">
          <img src="<?= e(media_url($event['image']) ?: 'https://placehold.co/400x300?text=Event') ?>" class="card-img-top" alt="">
          <div class="card-body"><span class="badge-soft mb-2"><?= e($event['status']) ?></span><h5 class="fw-bold"><?= e($event['title']) ?></h5><p class="text-muted mb-1"><i class="bi bi-clock me-2"></i><?= e($event['event_date']) ?> <?= e(substr($event['event_time'], 0, 5)) ?></p><p class="text-muted"><i class="bi bi-geo-alt me-2"></i><?= e($event['location']) ?></p><a class="btn btn-primary rounded-pill btn-sm" href="<?= SITE_URL ?>/event-detail.php?slug=<?= e($event['slug']) ?>">Event Details</a></div>
        </div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

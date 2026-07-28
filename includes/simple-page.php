<?php
$cards = $cards ?? [
    ['Professional Pathway', 'Placeholder guidance for applicants, members, and recognised practitioners.'],
    ['Development', 'Dummy notes about continuing practice, learning, and technical contribution.'],
    ['Recognition', 'Placeholder information about affiliations, awards, and professional standing.'],
];
$banner = page_banner($pdo, basename($_SERVER['SCRIPT_NAME']));
$heroTitle = banner_text($banner, 'title', $heading);
$heroSubtitle = banner_text($banner, 'subtitle', $subtitle);
$heroStyle = $banner ? ' style="background:linear-gradient(135deg, rgba(13,110,253,.70), rgba(168,85,247,.40)), url(' . e(media_url($banner['image'])) . ') center/cover; color:#fff;"' : '';
$muted = $banner ? '' : ' text-muted';
?>
<section class="page-hero"<?= $heroStyle ?>>
  <div class="container py-5">
    <span class="pill mb-3"><?= e($badge ?? 'SIET') ?></span>
    <h1 class="display-6"><?= e($heroTitle) ?></h1>
    <p class="lead<?= $muted ?> mb-0"><?= e($heroSubtitle) ?></p>
  </div>
</section>
<section class="container py-5">
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="lead-card p-4 about-card">
        <h2 class="section-title h4"><?= e($heading) ?></h2>
        <p class="text-muted lh-lg"><?= e($body) ?></p>
        <hr class="my-4">
        <p class="mb-0 lh-lg">This page uses dummy placeholder content only. Replace it with approved SIET copy, tables, documents, and imagery before publication.</p>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="d-grid gap-3">
        <?php foreach ($cards as $card): ?>
          <div class="about-side__card p-4">
            <h5 class="fw-bold"><?= e($card[0]) ?></h5>
            <p class="text-muted mb-0"><?= e($card[1]) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

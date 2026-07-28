<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$stmt = $pdo->prepare("SELECT * FROM site_pages WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([sanitize($_GET['slug'] ?? '')]);
$page = $stmt->fetch();
if (!$page) {
    $pageTitle = 'Page Not Found';
    require_once __DIR__ . '/includes/header.php';
    http_response_code(404);
    page_intro('Page Not Found', 'This custom page is not published or does not exist.');
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $page['title'];
require_once __DIR__ . '/includes/header.php';
?>
<?php
$banner = page_banner($pdo, 'page:' . $page['slug']) ?: page_banner($pdo, 'page.php');
$heroTitle = banner_text($banner, 'title', $page['title']);
$heroSubtitle = banner_text($banner, 'subtitle', $page['excerpt'] ?? '');
$heroStyle = $banner ? ' style="background:linear-gradient(135deg, rgba(13,110,253,.70), rgba(168,85,247,.40)), url(' . e(media_url($banner['image'])) . ') center/cover; color:#fff;"' : '';
$muted = $banner ? '' : ' text-muted';
?>
<section class="page-hero"<?= $heroStyle ?>><div class="container py-5"><span class="pill mb-3">Custom Page</span><h1 class="display-6"><?= e($heroTitle) ?></h1><p class="lead<?= $muted ?> mb-0"><?= e($heroSubtitle) ?></p></div></section>
<section class="container py-5"><div class="lead-card p-4"><div class="lh-lg"><?= nl2br(e($page['content'] ?? '')) ?></div></div></section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

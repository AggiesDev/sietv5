<?php
if (!ob_get_level()) {
    ob_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? SITE_NAME;
$flashes = consume_flash();
$customNav = public_nav_tree($pdo);
$customNavPageIds = [];
foreach ($customNav as $item) {
    if (empty($item['is_header']) && !empty($item['page_id'])) {
        $customNavPageIds[] = (int) $item['page_id'];
    }
    foreach ($item['children'] as $child) {
        if (empty($child['is_header']) && !empty($child['page_id'])) {
            $customNavPageIds[] = (int) $child['page_id'];
        }
    }
}
$shownCustomPages = published_nav_pages($pdo, array_unique($customNavPageIds));
$searchItems = [
    ['Home', 'index.php'],
    ['About SIET - Introduction', 'introduction.php'],
    ['About SIET - Organisation Structure', 'organisation.php'],
    ['About SIET - Our Honorary Patron & Fellows', 'fellows.php'],
    ['About SIET - Executive Council', 'council.php'],
    ['About SIET - Our Founders and Past Councils', 'founders.php'],
    ['About SIET - History and Milestones', 'history.php'],
    ['Membership - Why Become SIET Member', 'why-member.php'],
    ['Membership - Membership Pathways', 'membership-pathways.php'],
    ['Membership - Mature Candidate Scheme', 'mature-candidate.php'],
    ['Membership - Students as Members', 'students-members.php'],
    ['Membership - Membership & Certification Search', 'member-directory.php'],
    ['Membership - Membership Fees', 'membership-fees.php'],
    ['Certification - Professional Examinations', 'professionalexaminations.php'],
    ['Certification - Certification & Progression', 'cert-vs-membership.php'],
    ['Certification - SIET - TPC', 'siet-tpc.php'],
    ['Certification - Certification Application', 'cert-application.php'],
    ['Certification - Certification Fees', 'cert-fees.php'],
    ['Accreditation - Introduction', 'accreditation.php'],
    ['Accreditation - International Accreditation', 'accreditation-international.php'],
    ['Accreditation - Local Accreditation', 'accreditation-local.php'],
    ['Accreditation - Accredited Courses', 'accredited-courses.php'],
    ['Renewal & CPD - Renewal of Professional Registration', 'cpd-renewal.php'],
    ['Renewal & CPD - Types of CPD', 'cpd-types.php'],
    ['Global Network - Founding Member & Member', 'global-founding.php'],
    ['Global Network - International Recognitions', 'global-recognitions.php'],
    ['Global Network - Global Recognitions & Affiliations', 'global-affiliations.php'],
    ['Partnerships & Recognition - Why & How to become SIET Partner', 'organisational-partner.php'],
    ['Partnerships & Recognition - Our Organisation Partners', 'organisational-partnership.php'],
    ['Partnerships & Recognition - Sponsorship', 'sponsorship.php'],
    ['Partnerships & Recognition - Awards', 'awards.php'],
    ['Partnerships & Recognition - Expert Services', 'resources.php'],
    ['Partnerships & Recognition - Events', 'events.php'],
    ['Partnerships & Recognition - News', 'news.php'],
    ['Publications - Circulars', 'circulars.php'],
    ['Publications - Media', 'media.php'],
    ['Publications - Technical', 'technical.php'],
    ['Contact Us', 'contact.php'],
];
if ($customNav) {
    foreach ($customNav as $item) {
        if (!$item['is_header']) {
            $searchItems[] = [$item['label'], nav_url($item)];
        }
        foreach ($item['children'] as $child) {
            if (!$child['is_header']) {
                $searchItems[] = [$item['label'] . ' - ' . $child['label'], nav_url($child)];
            }
        }
    }
}
foreach ($shownCustomPages as $page) {
    $searchItems[] = ['Custom Pages - ' . $page['title'], 'page.php?slug=' . $page['slug']];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= SITE_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= SITE_URL ?>/assets/css/custom.css" rel="stylesheet">
</head>
<body class="smooth-scroll" data-pageid="<?= e(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME)) ?>">
<div class="siet-topbar">
  <div class="container-fluid px-3 px-lg-5">
    <div class="siet-topbar__inner">
      <div class="siet-topbar__left">
        <span class="siet-topbar__badge">Official Portal</span>
        <span class="siet-topbar__text d-none d-md-inline">SIET professional web portal placeholder</span>
      </div>
      <div class="siet-topbar__right">
        <?php if (is_logged_in()): ?>
          <span class="siet-topbar__hello d-none d-sm-inline">Hello, <?= e(current_user()['name']) ?></span>
          <a href="<?= is_admin() ? SITE_URL . '/admin/index.php' : SITE_URL . '/profile.php' ?>" class="btn btn-primary btn-sm siet-topbar__btn">Dashboard</a>
          <a href="<?= SITE_URL ?>/logout.php" class="btn btn-outline-light btn-sm siet-topbar__btn">Logout</a>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/login.php" class="btn btn-primary btn-sm siet-topbar__btn">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<nav class="navbar navbar-expand-xl navbar-light bg-white shadow-sm sticky-top">
  <div class="container-fluid px-3 px-lg-5">
    <a class="navbar-brand brand-stack" href="<?= SITE_URL ?>/index.php" aria-label="SIET Home">
      <img src="https://placehold.co/620x92/ffffff/0d6efd?text=SIET+Professional+Portal" alt="SIET placeholder banner logo" class="brand-logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mb-2 mb-xl-0 align-items-xl-center navbar-nav-wrap">
        <li class="nav-item w-100 d-xl-none mb-3">
          <div class="nav-search-mobile">
            <input id="navSearchInputMobile" class="form-control" type="search" placeholder="Search..." autocomplete="off">
            <div id="navSearchResultsMobile" class="nav-search-results" aria-label="Search results"></div>
          </div>
        </li>
        <?php if ($customNav): ?>
          <?php foreach ($customNav as $item): ?>
            <?php if ($item['children']): ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= e($item['label']) ?></a>
                <ul class="dropdown-menu">
                  <?php foreach ($item['children'] as $child): ?>
                    <?php if ($child['is_header']): ?>
                      <li><h5 class="dropdown-header"><?= e($child['label']) ?></h5></li>
                    <?php else: ?>
                      <li><a class="dropdown-item" href="<?= e(nav_url($child)) ?>"><?= e($child['label']) ?></a></li>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </ul>
              </li>
            <?php elseif ($item['is_header'] && empty($item['url']) && empty($item['page_slug'])): ?>
              <li class="nav-item"><span class="nav-link disabled"><?= e($item['label']) ?></span></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="<?= e(nav_url($item)) ?>"><?= e($item['label']) ?></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
        <li class="nav-item"><a class="nav-link <?= active_class('index.php') ?>" href="<?= SITE_URL ?>/index.php">Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">About SIET</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/introduction.php">Introduction</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/organisation.php">Organisation Structure</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/fellows.php">Our Honorary Patron &amp; Fellows</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/council.php">Executive Council</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/founders.php">Our Founders and Past Councils</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/history.php">History and Milestones</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Membership</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/why-member.php">Why Become SIET Member</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/membership-pathways.php">Membership Pathways</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/mature-candidate.php">Mature Candidate Scheme</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/students-members.php">Students as Members</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/member-directory.php">Membership &amp; Certification Search</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/membership-fees.php">Membership Fees</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Certification</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/professionalexaminations.php">Professional Examinations</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/cert-vs-membership.php">Certification &amp; Progression</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/siet-tpc.php">SIET - TPC</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/cert-application.php">Certification Application</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/cert-fees.php">Certification Fees</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Accreditation</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/accreditation.php">Introduction</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/accreditation-international.php">International Accreditation</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/accreditation-local.php">Local Accreditation</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/accredited-courses.php">Accredited Courses</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Renewal &amp; CPD</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/cpd-renewal.php">Renewal of Professional Registration</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/cpd-types.php">Types of CPD</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Global Network</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/global-founding.php">Founding Member &amp; Member</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/global-recognitions.php">International Recognitions</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/global-affiliations.php">Global Recognitions &amp; Affiliations</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Partnerships &amp; Recognition</a>
          <ul class="dropdown-menu">
            <li><h5 class="dropdown-header">Organisational Partnership</h5></li>
            <li><a class="dropdown-item organisationl" href="<?= SITE_URL ?>/organisational-partner.php">Why &amp; How to become SIET Partner</a></li>
            <li><a class="dropdown-item organisationl" href="<?= SITE_URL ?>/organisational-partnership.php">Our Organisation Partners</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h5 class="dropdown-header">Recognition</h5></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/sponsorship.php">Sponsorship</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/awards.php">Awards</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h5 class="dropdown-header">Resources</h5></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/resources.php">Expert Services</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/events.php">Events</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/news.php">News</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/publications.php">Publications</a></li>
          </ul>
        </li>
        <?php endif; ?>
        <?php if ($shownCustomPages): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Custom Pages</a>
            <ul class="dropdown-menu">
              <?php foreach ($shownCustomPages as $page): ?>
                <li><a class="dropdown-item" href="<?= SITE_URL ?>/page.php?slug=<?= e($page['slug']) ?>"><?= e($page['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endif; ?>
        <li class="nav-item nav-search-desktop align-items-center">
          <button id="navSearchToggle" class="nav-link btn btn-link nav-search-icon" type="button" aria-label="Toggle search" aria-expanded="false">
            <span class="nav-search-glyph" aria-hidden="true">🔍</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div id="navSearchPanel" class="nav-search-panel" hidden>
  <div class="container-fluid px-3 px-lg-5">
    <div class="nav-search-panel__inner">
      <input id="navSearchInput" class="form-control" type="search" placeholder="Search..." autocomplete="off">
      <div id="navSearchResults" class="nav-search-results" aria-label="Search results"></div>
    </div>
  </div>
</div>

<script>
window.__NAV_SEARCH_ITEMS__ = <?= json_encode($searchItems) ?>;
</script>

<main>
<?php if ($flashes): ?>
<div class="toast-container position-fixed top-0 end-0 p-3 mt-5">
  <?php foreach ($flashes as $flash): ?>
    <div class="toast show text-bg-<?= e($flash['type']) ?> border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body"><?= e($flash['message']) ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

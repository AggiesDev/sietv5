<?php
$pageTitle = 'Search';
require_once __DIR__ . '/_header.php';

$q = sanitize($_GET['q'] ?? '');
$like = '%' . $q . '%';
$groups = [];

function admin_search_rows(PDO $pdo, string $sql, array $params): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

if ($q !== '') {
    $groups['Members'] = admin_search_rows(
        $pdo,
        "SELECT name AS title, CONCAT(email, ' / ', COALESCE(membership_no, ''), ' / ', COALESCE(certification_no, '')) AS detail, 'members.php?q=' AS base_url FROM users WHERE role='member' AND (name LIKE ? OR email LIKE ? OR membership_no LIKE ? OR certification_no LIKE ? OR membership_grade LIKE ? OR branch LIKE ? OR specialisation LIKE ?) ORDER BY created_at DESC LIMIT 8",
        array_fill(0, 7, $like)
    );
    $groups['Applications'] = admin_search_rows(
        $pdo,
        "SELECT full_name AS title, CONCAT(email, ' / ', grade_applied, ' / ', status) AS detail, 'members.php?q=' AS base_url FROM membership_applications WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR grade_applied LIKE ? OR status LIKE ? ORDER BY submitted_at DESC LIMIT 8",
        array_fill(0, 5, $like)
    );
    $groups['News'] = admin_search_rows(
        $pdo,
        "SELECT title, CONCAT(status, ' / ', slug) AS detail, 'news.php?q=' AS base_url FROM news WHERE title LIKE ? OR slug LIKE ? OR excerpt LIKE ? OR content LIKE ? ORDER BY created_at DESC LIMIT 8",
        array_fill(0, 4, $like)
    );
    $groups['Events'] = admin_search_rows(
        $pdo,
        "SELECT title, CONCAT(status, ' / ', COALESCE(location, ''), ' / ', COALESCE(event_date, '')) AS detail, 'events.php?q=' AS base_url FROM events WHERE title LIKE ? OR slug LIKE ? OR description LIKE ? OR location LIKE ? ORDER BY event_date DESC LIMIT 8",
        array_fill(0, 4, $like)
    );
    $groups['Partners'] = admin_search_rows(
        $pdo,
        "SELECT name AS title, CONCAT(type, ' / ', status, ' / ', COALESCE(website, '')) AS detail, 'partners.php?q=' AS base_url FROM partners WHERE name LIKE ? OR type LIKE ? OR description LIKE ? OR website LIKE ? OR status LIKE ? ORDER BY name LIMIT 8",
        array_fill(0, 5, $like)
    );
    $groups['Council'] = admin_search_rows(
        $pdo,
        "SELECT name AS title, CONCAT(COALESCE(position, ''), ' / ', COALESCE(period, '')) AS detail, 'council.php?q=' AS base_url FROM council WHERE name LIKE ? OR position LIKE ? OR period LIKE ? OR bio LIKE ? ORDER BY display_order, name LIMIT 8",
        array_fill(0, 4, $like)
    );
    $groups['Pages'] = admin_search_rows(
        $pdo,
        "SELECT title, CONCAT(status, ' / page:', slug) AS detail, 'pages.php?q=' AS base_url FROM site_pages WHERE title LIKE ? OR slug LIKE ? OR excerpt LIKE ? OR content LIKE ? ORDER BY updated_at DESC, created_at DESC LIMIT 8",
        array_fill(0, 4, $like)
    );
    $groups['Navigation'] = admin_search_rows(
        $pdo,
        "SELECT n.label AS title, CONCAT(COALESCE(n.url, ''), ' / ', COALESCE(p.title, '')) AS detail, 'nav.php?q=' AS base_url FROM nav_items n LEFT JOIN site_pages p ON p.id = n.page_id WHERE n.label LIKE ? OR n.url LIKE ? OR p.title LIKE ? OR p.slug LIKE ? ORDER BY n.sort_order, n.label LIMIT 8",
        array_fill(0, 4, $like)
    );
    $groups['Banners'] = admin_search_rows(
        $pdo,
        "SELECT COALESCE(title, page_key, scope) AS title, CONCAT(scope, ' / ', COALESCE(page_key, 'home')) AS detail, 'banners.php?q=' AS base_url FROM site_banners WHERE page_key LIKE ? OR title LIKE ? OR subtitle LIKE ? OR button_label LIKE ? OR button_url LIKE ? ORDER BY scope, page_key, sort_order LIMIT 8",
        array_fill(0, 5, $like)
    );
    $groups['Messages'] = admin_search_rows(
        $pdo,
        "SELECT subject AS title, CONCAT(name, ' / ', email) AS detail, 'messages.php?q=' AS base_url FROM contact_messages WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ? ORDER BY created_at DESC LIMIT 8",
        array_fill(0, 4, $like)
    );
}

$total = array_sum(array_map('count', $groups));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h4 class="mb-0">Admin Search</h4><p class="text-muted mb-0">Search across members, content, website controls, and messages.</p></div>
</div>
<form class="card card-body mb-4" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-9"><label class="form-label">Search all admin data</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Name, email, page key, title, member no, status"></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/search.php">Clear</a></div>
  </div>
</form>
<?php if ($q === ''): ?>
  <div class="alert alert-info border-0 shadow-sm">Enter a keyword to search all managed website data.</div>
<?php elseif (!$total): ?>
  <div class="alert alert-warning border-0 shadow-sm">No admin data matched <strong><?= e($q) ?></strong>.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($groups as $label => $rows): ?>
      <?php if (!$rows) continue; ?>
      <div class="col-lg-6"><div class="card card-body h-100">
        <div class="d-flex justify-content-between mb-2"><h5 class="mb-0"><?= e($label) ?></h5><span class="badge text-bg-primary"><?= count($rows) ?></span></div>
        <div class="list-group list-group-flush">
          <?php foreach ($rows as $row): ?>
            <a class="list-group-item list-group-item-action px-0" href="<?= SITE_URL ?>/admin/<?= e($row['base_url'] . urlencode($q)) ?>">
              <div class="fw-semibold"><?= e($row['title']) ?></div>
              <div class="small text-muted"><?= e($row['detail']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/_footer.php'; ?>

<?php
$pageTitle = 'Pages';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare('DELETE FROM site_pages WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Page deleted.');
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $status = in_array($_POST['status'] ?? 'draft', ['published', 'draft'], true) ? $_POST['status'] : 'draft';
        $show = isset($_POST['show_in_nav']) ? 1 : 0;
        $slug = unique_slug($pdo, 'site_pages', $title, $id ?: null);
        if ($id) {
            $stmt = $pdo->prepare('UPDATE site_pages SET title=?, slug=?, excerpt=?, content=?, status=?, show_in_nav=? WHERE id=?');
            $stmt->execute([$title, $slug, sanitize($_POST['excerpt'] ?? ''), trim($_POST['content'] ?? ''), $status, $show, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO site_pages (title, slug, excerpt, content, status, show_in_nav, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $slug, sanitize($_POST['excerpt'] ?? ''), trim($_POST['content'] ?? ''), $status, $show, current_user()['id']]);
        }
        flash('success', 'Page saved.');
    }
    header('Location: ' . SITE_URL . '/admin/pages.php');
    exit;
}

require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$statusFilter = in_array($_GET['status'] ?? '', ['published', 'draft'], true) ? $_GET['status'] : '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR slug LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
if ($statusFilter) {
    $where[] = 'status=?';
    $params[] = $statusFilter;
}
$stmt = $pdo->prepare('SELECT * FROM site_pages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY updated_at DESC, created_at DESC');
$stmt->execute($params);
$pages = $stmt->fetchAll();
$pageCatalog = site_page_catalog($pdo);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h4 class="mb-0">Custom Pages</h4><p class="text-muted mb-0">Create pages, publish them, and optionally show them on the public navbar.</p></div>
  <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#pageModal"><i class="bi bi-plus-lg me-1"></i>New Page</button>
</div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-7"><label class="form-label">Search custom pages</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Title, slug, excerpt, content"></div>
  <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="published" <?= $statusFilter==='published'?'selected':'' ?>>published</option><option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>draft</option></select></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/pages.php">Clear</a></div>
</div></form>
<div class="card card-body mb-4">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
    <h5 class="mb-0">Current User Site Pages</h5>
    <span class="text-muted small">These are the public pages admin can connect to banners and navigation.</span>
  </div>
  <div class="row g-2">
    <?php foreach ($pageCatalog as $key => $label): ?>
      <?php $url = str_starts_with($key, 'page:') ? SITE_URL . '/page.php?slug=' . urlencode(substr($key, 5)) : SITE_URL . '/' . $key; ?>
      <div class="col-md-6 col-xl-4"><div class="border rounded-3 p-2 h-100">
        <div class="fw-semibold small"><?= e($label) ?></div>
        <code><?= e($key) ?></code>
        <div><a class="small" href="<?= e($url) ?>" target="_blank">Open page</a></div>
      </div></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="table-responsive"><table class="table table-striped table-hover bg-white align-middle"><thead><tr><th>Title</th><th>Slug</th><th>Banner Key</th><th>Status</th><th>Nav</th><th>URL</th><th></th></tr></thead><tbody>
<?php foreach ($pages as $page): ?>
  <tr>
    <td class="fw-semibold"><?= e($page['title']) ?></td><td><?= e($page['slug']) ?></td><td><code>page:<?= e($page['slug']) ?></code></td><td><?= e($page['status']) ?></td><td><?= $page['show_in_nav'] ? 'Shown' : 'Hidden' ?></td>
    <td><a href="<?= SITE_URL ?>/page.php?slug=<?= e($page['slug']) ?>" target="_blank">View</a></td>
    <td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#pageModal" data-item='<?= e(json_encode($page)) ?>'><i class="bi bi-pencil"></i></button>
    <form class="d-inline" method="post" onsubmit="return confirm('Delete this page?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $page['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td>
  </tr>
<?php endforeach; ?>
</tbody></table></div>

<div class="modal fade" id="pageModal"><div class="modal-dialog modal-xl"><form method="post" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Custom Page</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
    <div class="col-md-8"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option>draft</option><option>published</option></select></div>
    <div class="col-12"><label class="form-label">Excerpt</label><textarea name="excerpt" class="form-control" rows="2"></textarea></div>
    <div class="col-12"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="10"></textarea></div>
    <div class="col-12"><label class="form-check"><input type="checkbox" name="show_in_nav" value="1" class="form-check-input"> Show on public navbar under Custom Pages</label></div>
  </div></div>
  <div class="modal-footer"><button class="btn btn-primary rounded-pill px-4">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

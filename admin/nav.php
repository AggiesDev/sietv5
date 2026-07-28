<?php
$pageTitle = 'Navigation';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'seed_default') {
        seed_default_navigation($pdo, true);
        flash('success', 'Default SIET navigation loaded into the manager. You can now edit, hide, reorder, or add custom pages inside this flow.');
    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare('DELETE FROM nav_items WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Navigation item deleted.');
    } else {
        $pageId = (int) ($_POST['page_id'] ?? 0);
        $url = sanitize($_POST['url'] ?? '');
        if ($pageId) {
            $url = 'page.php?slug=' . (string) $pdo->query('SELECT slug FROM site_pages WHERE id=' . $pageId)->fetchColumn();
        }
        $stmtParams = [
            sanitize($_POST['label'] ?? ''),
            $url,
            ($_POST['parent_id'] ?? '') === '' ? null : (int) $_POST['parent_id'],
            $pageId ?: null,
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['is_header']) ? 1 : 0,
        ];
        if ($id) {
            $stmt = $pdo->prepare('UPDATE nav_items SET label=?, url=?, parent_id=?, page_id=?, sort_order=?, is_active=?, is_header=? WHERE id=?');
            $stmt->execute([...$stmtParams, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO nav_items (label, url, parent_id, page_id, sort_order, is_active, is_header) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute($stmtParams);
        }
        flash('success', 'Navigation item saved.');
    }
    header('Location: ' . SITE_URL . '/admin/nav.php');
    exit;
}

require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$navWhere = '';
$navParams = [];
if ($q !== '') {
    $navWhere = ' WHERE n.label LIKE ? OR n.url LIKE ? OR p.title LIKE ? OR p.slug LIKE ?';
    $navParams = array_fill(0, 4, '%' . $q . '%');
}
$stmt = $pdo->prepare('SELECT n.*, p.title AS page_title FROM nav_items n LEFT JOIN site_pages p ON p.id = n.page_id' . $navWhere . ' ORDER BY COALESCE(n.parent_id, n.id), n.parent_id IS NOT NULL, n.sort_order, n.label');
$stmt->execute($navParams);
$items = $stmt->fetchAll();
$parents = $pdo->query('SELECT * FROM nav_items WHERE parent_id IS NULL ORDER BY sort_order, label')->fetchAll();
$pages = $pdo->query("SELECT id, title FROM site_pages WHERE status='published' ORDER BY title")->fetchAll();
$tree = public_nav_tree($pdo);
$shownCustomPages = published_nav_pages($pdo);
$defaultFlow = [];
foreach (default_nav_blueprint() as $top) {
    $defaultFlow[$top[0]] = array_map(fn($child) => $child[0], $top[2]);
}
if ($shownCustomPages) {
    $defaultFlow['Custom Pages'] = array_column($shownCustomPages, 'title');
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h4 class="mb-0">Navigation Flow</h4><p class="text-muted mb-0">Create menu groups, child links, ordering, and visibility.</p></div>
  <div class="d-flex gap-2">
    <form method="post" onsubmit="return confirm('Replace all current nav items with the default SIET navigation flow?')">
      <input type="hidden" name="action" value="seed_default">
      <button class="btn btn-outline-primary rounded-pill"><i class="bi bi-diagram-3 me-1"></i>Load Default SIET Nav</button>
    </form>
    <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#navModal"><i class="bi bi-plus-lg me-1"></i>New Item</button>
  </div>
</div>
<div class="alert alert-info border-0 shadow-sm">When at least one active nav item exists, the public navbar uses this managed navigation. Use <strong>Load Default SIET Nav</strong> to bring every default menu item into admin control, then edit, hide, reorder, or place custom pages anywhere.</div>
<form class="card card-body mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-9"><label class="form-label">Search navigation items</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Menu label, URL, custom page title, slug"></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/nav.php">Clear</a></div>
  </div>
</form>
<?php if ($shownCustomPages): ?>
  <div class="alert alert-success border-0 shadow-sm">Published pages marked <strong>Show in nav</strong> are visible on the public site under <strong>Custom Pages</strong>. Add them here as nav items only when you want a specific custom position.</div>
<?php endif; ?>
<div class="card card-body mb-4">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <h5 class="mb-0">Current Public Navbar Flow</h5>
    <span class="badge text-bg-<?= $tree ? 'primary' : 'secondary' ?>"><?= $tree ? 'Custom flow active' : 'Default reference flow active' ?></span>
  </div>
  <div class="row g-3">
    <?php if ($tree): ?>
      <?php foreach ($tree as $top): ?>
        <div class="col-md-6 col-xl-3"><div class="border rounded-3 p-3 h-100"><div class="fw-bold"><?= e($top['label']) ?><?= ($top['is_header'] && !$top['children']) ? ' <span class="badge text-bg-warning">header flag</span>' : '' ?></div><?php if (!$top['children'] && ($top['url'] || $top['page_slug'])): ?><div class="small text-muted mt-1"><?= e(nav_url($top)) ?></div><?php endif; ?><?php foreach ($top['children'] as $child): ?><div class="small text-muted mt-1"><?= $child['is_header'] ? '<strong>' . e($child['label']) . '</strong>' : e($child['label']) ?></div><?php endforeach; ?></div></div>
      <?php endforeach; ?>
      <?php if ($shownCustomPages): ?>
        <div class="col-md-6 col-xl-3"><div class="border rounded-3 p-3 h-100"><div class="fw-bold">Custom Pages</div><?php foreach ($shownCustomPages as $page): ?><div class="small text-muted mt-1"><?= e($page['title']) ?></div><?php endforeach; ?></div></div>
      <?php endif; ?>
    <?php else: ?>
      <?php foreach ($defaultFlow as $top => $children): ?>
        <div class="col-md-6 col-xl-3"><div class="border rounded-3 p-3 h-100"><div class="fw-bold"><?= e($top) ?></div><?php foreach ($children as $child): ?><div class="small text-muted mt-1"><?= e($child) ?></div><?php endforeach; ?></div></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<div class="table-responsive"><table class="table table-striped table-hover bg-white align-middle"><thead><tr><th>Order</th><th>Label</th><th>Parent</th><th>URL/Page</th><th>Status</th><th>Header</th><th></th></tr></thead><tbody>
<?php foreach ($items as $item): ?>
  <?php $parentLabel = ''; foreach ($parents as $parent) { if ((int) $parent['id'] === (int) $item['parent_id']) $parentLabel = $parent['label']; } ?>
  <tr><td><?= (int) $item['sort_order'] ?></td><td class="fw-semibold"><?= e($item['label']) ?></td><td><?= e($parentLabel ?: 'Top level') ?></td><td><?= e($item['page_title'] ?: $item['url']) ?></td><td><?= $item['is_active'] ? 'Visible' : 'Hidden' ?></td><td><?= $item['is_header'] ? 'Yes' : 'No' ?></td><td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#navModal" data-item='<?= e(json_encode($item)) ?>'><i class="bi bi-pencil"></i></button> <form class="d-inline" method="post" onsubmit="return confirm('Delete this nav item?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
<?php endforeach; ?>
</tbody></table></div>

<div class="modal fade" id="navModal"><div class="modal-dialog modal-lg"><form method="post" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Navigation Item</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Label</label><input name="label" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
    <div class="col-md-3"><label class="form-label">Parent</label><select name="parent_id" class="form-select"><option value="">Top level</option><?php foreach ($parents as $parent): ?><option value="<?= (int) $parent['id'] ?>"><?= e($parent['label']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Custom Page</label><select name="page_id" class="form-select"><option value="">Use URL below</option><?php foreach ($pages as $page): ?><option value="<?= (int) $page['id'] ?>"><?= e($page['title']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">URL</label><input name="url" class="form-control" placeholder="events.php or https://example.com"></div>
    <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> Visible on website</label></div>
    <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_header" value="1"> Dropdown section header only</label><div class="form-text">Use this only for non-clickable labels inside a dropdown, like Recognition or Resources.</div></div>
  </div></div>
  <div class="modal-footer"><button class="btn btn-primary rounded-pill px-4">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

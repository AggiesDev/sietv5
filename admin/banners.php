<?php
$pageTitle = 'Banners';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'save';
        $id = (int) ($_POST['id'] ?? 0);
        if ($action === 'delete' && $id) {
            $stmt = $pdo->prepare('SELECT image FROM site_banners WHERE id = ?');
            $stmt->execute([$id]);
            $oldImage = (string) $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM site_banners WHERE id = ?');
            $stmt->execute([$id]);
            delete_uploaded_file($oldImage);
            flash('success', 'Banner deleted.');
        } else {
            $scope = ($_POST['scope'] ?? 'page') === 'home' ? 'home' : 'page';
            if ($scope === 'home' && !$id && count_rows($pdo, 'site_banners', "scope='home'") >= 5) {
                throw new RuntimeException('Home banner limit is 5 images. Disable or delete an existing banner first.');
            }
            $pageKey = sanitize($_POST['page_key'] ?? '');
            if ($scope === 'page' && !$pageKey) {
                throw new RuntimeException('Choose the page this banner belongs to.');
            }
            $oldImage = '';
            if ($id) {
                $stmt = $pdo->prepare('SELECT image FROM site_banners WHERE id = ?');
                $stmt->execute([$id]);
                $oldImage = (string) $stmt->fetchColumn();
            }
            $uploadedImage = upload_image('image');
            $image = $uploadedImage ?: sanitize($_POST['existing_image'] ?? '');
            if (!$image) {
                throw new RuntimeException('Please upload an image or keep an existing one.');
            }
            $params = [
                $scope,
                $scope === 'page' ? $pageKey : null,
                sanitize($_POST['title'] ?? ''),
                sanitize($_POST['subtitle'] ?? ''),
                $image,
                sanitize($_POST['button_label'] ?? ''),
                sanitize($_POST['button_url'] ?? ''),
                (int) ($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id) {
                $stmt = $pdo->prepare('UPDATE site_banners SET scope=?, page_key=?, title=?, subtitle=?, image=?, button_label=?, button_url=?, sort_order=?, is_active=? WHERE id=?');
                $stmt->execute([...$params, $id]);
                if ($uploadedImage && $oldImage && media_url($oldImage) !== media_url($uploadedImage)) {
                    delete_uploaded_file($oldImage);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO site_banners (scope, page_key, title, subtitle, image, button_label, button_url, sort_order, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([...$params, current_user()['id']]);
            }
            flash('success', 'Banner saved.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    header('Location: ' . SITE_URL . '/admin/banners.php');
    exit;
}

require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$scopeFilter = in_array($_GET['scope'] ?? '', ['home', 'page'], true) ? $_GET['scope'] : '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(page_key LIKE ? OR title LIKE ? OR subtitle LIKE ? OR button_label LIKE ? OR button_url LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $q . '%'));
}
if ($scopeFilter) {
    $where[] = 'scope=?';
    $params[] = $scopeFilter;
}
$stmt = $pdo->prepare('SELECT * FROM site_banners' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY scope, page_key, sort_order, created_at DESC');
$stmt->execute($params);
$banners = $stmt->fetchAll();
$knownPages = site_page_catalog($pdo);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h4 class="mb-0">Banner Control</h4><p class="text-muted mb-0">Manage up to 5 active/home slider images and page-specific banners.</p></div>
  <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#bannerModal"><i class="bi bi-plus-lg me-1"></i>New Banner</button>
</div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-7"><label class="form-label">Search banners</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Page key, title, subtitle, button"></div>
  <div class="col-md-2"><label class="form-label">Scope</label><select name="scope" class="form-select"><option value="">All</option><option value="home" <?= $scopeFilter==='home'?'selected':'' ?>>home</option><option value="page" <?= $scopeFilter==='page'?'selected':'' ?>>page</option></select></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/banners.php">Clear</a></div>
</div></form>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card card-body"><div class="text-muted small">Home banners</div><h3><?= count_rows($pdo, 'site_banners', "scope='home'") ?>/5</h3></div></div>
  <div class="col-md-4"><div class="card card-body"><div class="text-muted small">Active banners</div><h3><?= count_rows($pdo, 'site_banners', 'is_active=1') ?></h3></div></div>
  <div class="col-md-4"><div class="card card-body"><div class="text-muted small">Page banners</div><h3><?= count_rows($pdo, 'site_banners', "scope='page'") ?></h3></div></div>
</div>
<div class="card card-body mb-4">
  <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
    <h5 class="mb-0">Page Banner Keys</h5>
    <span class="text-muted small">Use these keys when connecting a banner to a public page.</span>
  </div>
  <div class="row g-2">
    <?php foreach ($knownPages as $key => $label): ?>
      <div class="col-md-6 col-xl-4"><div class="border rounded-3 p-2 h-100"><div class="fw-semibold small"><?= e($label) ?></div><code><?= e($key) ?></code></div></div>
    <?php endforeach; ?>
  </div>
</div>
<div class="table-responsive"><table class="table table-striped table-hover bg-white align-middle"><thead><tr><th>Preview</th><th>Scope</th><th>Page Key</th><th>Title</th><th>Order</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($banners as $banner): ?>
  <?php $bannerPayload = $banner; $bannerPayload['image'] = media_url($banner['image']); $bannerPayload['existing_image'] = media_url($banner['image']); ?>
  <tr><td><img src="<?= e(media_url($banner['image'])) ?>" class="rounded object-fit-cover" style="width:120px;height:54px" alt=""></td><td><?= e($banner['scope']) ?></td><td><?= e($banner['page_key']) ?></td><td class="fw-semibold"><?= e($banner['title']) ?></td><td><?= (int) $banner['sort_order'] ?></td><td><?= $banner['is_active'] ? 'Open' : 'Closed' ?></td><td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#bannerModal" data-item='<?= e(json_encode($bannerPayload)) ?>'><i class="bi bi-pencil"></i></button> <form class="d-inline" method="post" onsubmit="return confirm('Delete this banner?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $banner['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
<?php endforeach; ?>
</tbody></table></div>

<div class="modal fade" id="bannerModal"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Banner</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="existing_image"><div class="row g-3">
    <div class="col-md-4"><label class="form-label">Scope</label><select name="scope" class="form-select"><option value="home">home</option><option value="page">page</option></select></div>
    <div class="col-md-4"><label class="form-label">Page</label><select name="page_key" class="form-select"><option value="">Home banner or choose page...</option><?php foreach ($knownPages as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?> (<?= e($key) ?>)</option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label class="form-label">Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
    <div class="col-md-6"><label class="form-label">Title Override</label><input name="title" class="form-control" placeholder="Leave blank to keep current page title"><div class="form-text">Blank keeps the page's existing title.</div></div>
    <div class="col-md-6"><label class="form-label">Subtitle Override</label><input name="subtitle" class="form-control" placeholder="Leave blank to keep current page subtitle"><div class="form-text">Blank keeps the page's existing subtitle.</div></div>
    <div class="col-md-6"><label class="form-label">Button Label</label><input name="button_label" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Button URL</label><input name="button_url" class="form-control"></div>
    <div class="col-12"><label class="form-label">Current Image</label><div class="border rounded-3 p-2 bg-light"><img data-upload-preview="existing_image" src="" class="rounded object-fit-cover d-none" style="width:100%;max-height:220px" alt="Current banner image"><div data-upload-empty="existing_image" class="text-muted small">No image selected yet.</div></div></div>
    <div class="col-12"><label class="form-label">Replace Image</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
    <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked> Open/show this banner</label></div>
  </div></div>
  <div class="modal-footer"><button class="btn btn-primary rounded-pill px-4">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

<?php
$pageTitle = 'Partners';
require_once __DIR__ . '/_header.php';
$type = sanitize($_GET['type'] ?? '');
$q = sanitize($_GET['q'] ?? '');
$where = [];
$params = [];
if ($type) {
    $where[] = 'type=?';
    $params[] = $type;
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR type LIKE ? OR description LIKE ? OR website LIKE ? OR status LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $q . '%'));
}
$sql = 'SELECT * FROM partners' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><h4>Partners</h4><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#partnerModal"><i class="bi bi-plus-lg"></i></button></div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-6"><label class="form-label">Search partners</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Name, website, description, status"></div>
  <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select"><option value="">All types</option><option <?= $type==='local'?'selected':'' ?>>local</option><option <?= $type==='international'?'selected':'' ?>>international</option><option <?= $type==='organisational'?'selected':'' ?>>organisational</option></select></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/partners.php">Clear</a></div>
</div></form>
<div class="table-responsive"><table class="table table-striped table-hover bg-white"><thead><tr><th>Name</th><th>Type</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($items as $item): ?><?php $itemPayload = $item; $itemPayload['logo'] = media_url($item['logo']); $itemPayload['existing_logo'] = media_url($item['logo']); ?><tr><td><?= e($item['name']) ?></td><td><?= e($item['type']) ?></td><td><?= e($item['status']) ?></td><td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#partnerModal" data-item='<?= e(json_encode($itemPayload)) ?>'><i class="bi bi-pencil"></i></button> <form class="d-inline" method="post" action="<?= SITE_URL ?>/admin/ajax/delete.php" onsubmit="return confirm('Delete this partner?')"><input type="hidden" name="table" value="partners"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="modal fade" id="partnerModal"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" action="<?= SITE_URL ?>/admin/ajax/save_partner.php" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Partner</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="existing_logo"><div class="row g-3"><div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div><div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select"><option>local</option><option>international</option><option>organisational</option></select></div><div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option>active</option><option>inactive</option></select></div><div class="col-12"><label class="form-label">Website</label><input name="website" class="form-control"></div><div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"></textarea></div><div class="col-12"><label class="form-label">Current Logo</label><div class="border rounded-3 p-2 bg-light"><img data-upload-preview="existing_logo" src="" class="rounded object-fit-contain d-none" style="width:100%;max-height:160px" alt="Current logo"><div data-upload-empty="existing_logo" class="text-muted small">No logo selected yet.</div></div></div><div class="col-12"><label class="form-label">Replace Logo</label><input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div></div></div>
  <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

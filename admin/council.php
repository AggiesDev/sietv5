<?php
$pageTitle = 'Council';
require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT * FROM council';
if ($q !== '') {
    $sql .= ' WHERE name LIKE ? OR position LIKE ? OR period LIKE ? OR bio LIKE ?';
    $params = array_fill(0, 4, '%' . $q . '%');
}
$sql .= ' ORDER BY display_order, name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3"><h4>Council</h4><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#councilModal"><i class="bi bi-plus-lg"></i></button></div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-9"><label class="form-label">Search council</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Name, position, period, bio"></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/council.php">Clear</a></div>
</div></form>
<div class="table-responsive"><table class="table table-striped table-hover bg-white"><thead><tr><th>Order</th><th>Name</th><th>Position</th><th>Period</th><th></th></tr></thead><tbody>
<?php foreach ($items as $item): ?><?php $itemPayload = $item; $itemPayload['image'] = media_url($item['image']); $itemPayload['existing_image'] = media_url($item['image']); ?><tr><td><i class="bi bi-grip-vertical text-muted"></i> <?= (int) $item['display_order'] ?></td><td><?= e($item['name']) ?></td><td><?= e($item['position']) ?></td><td><?= e($item['period']) ?></td><td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#councilModal" data-item='<?= e(json_encode($itemPayload)) ?>'><i class="bi bi-pencil"></i></button> <form class="d-inline" method="post" action="<?= SITE_URL ?>/admin/ajax/delete.php" onsubmit="return confirm('Delete this council member?')"><input type="hidden" name="table" value="council"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="modal fade" id="councilModal"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" action="<?= SITE_URL ?>/admin/ajax/save_council.php" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Council Member</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="existing_image"><div class="row g-3"><div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div><div class="col-md-6"><label class="form-label">Position</label><input name="position" class="form-control"></div><div class="col-md-6"><label class="form-label">Period</label><input name="period" class="form-control"></div><div class="col-md-6"><label class="form-label">Display Order</label><input type="number" name="display_order" class="form-control"></div><div class="col-12"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="4"></textarea></div><div class="col-12"><label class="form-label">Current Image</label><div class="border rounded-3 p-2 bg-light"><img data-upload-preview="existing_image" src="" class="rounded object-fit-cover d-none" style="width:100%;max-height:180px" alt="Current image"><div data-upload-empty="existing_image" class="text-muted small">No image selected yet.</div></div></div><div class="col-12"><label class="form-label">Replace Image</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div></div></div>
  <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

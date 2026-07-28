<?php
$pageTitle = 'Events';
require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$status = in_array($_GET['status'] ?? '', ['upcoming', 'ongoing', 'completed'], true) ? $_GET['status'] : '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR slug LIKE ? OR description LIKE ? OR location LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
if ($status) {
    $where[] = 'status=?';
    $params[] = $status;
}
$stmt = $pdo->prepare('SELECT * FROM events' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY event_date DESC');
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between mb-3"><h4>Events</h4><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal"><i class="bi bi-plus-lg"></i></button></div>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-7"><label class="form-label">Search events</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Title, location, description"></div>
  <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="upcoming" <?= $status==='upcoming'?'selected':'' ?>>upcoming</option><option value="ongoing" <?= $status==='ongoing'?'selected':'' ?>>ongoing</option><option value="completed" <?= $status==='completed'?'selected':'' ?>>completed</option></select></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/events.php">Clear</a></div>
</div></form>
<div class="table-responsive"><table class="table table-striped table-hover bg-white"><thead><tr><th>Title</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($items as $item): ?><?php $itemPayload = $item; $itemPayload['image'] = media_url($item['image']); $itemPayload['existing_image'] = media_url($item['image']); ?><tr><td><?= e($item['title']) ?></td><td><?= e($item['event_date']) ?></td><td><?= e($item['status']) ?></td><td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#eventModal" data-item='<?= e(json_encode($itemPayload)) ?>'><i class="bi bi-pencil"></i></button> <form class="d-inline" method="post" action="<?= SITE_URL ?>/admin/ajax/delete.php" onsubmit="return confirm('Delete this event?')"><input type="hidden" name="table" value="events"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="modal fade" id="eventModal"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" action="<?= SITE_URL ?>/admin/ajax/save_event.php" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Event</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="existing_image"><div class="row g-3"><div class="col-md-8"><label class="form-label">Title</label><input name="title" class="form-control" required></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option>upcoming</option><option>ongoing</option><option>completed</option></select></div><div class="col-md-4"><label class="form-label">Date</label><input type="date" name="event_date" class="form-control"></div><div class="col-md-4"><label class="form-label">Time</label><input type="time" name="event_time" class="form-control"></div><div class="col-md-4"><label class="form-label">Max Participants</label><input type="number" name="max_participants" class="form-control"></div><div class="col-12"><label class="form-label">Location</label><input name="location" class="form-control"></div><div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="6"></textarea></div><div class="col-12"><label class="form-label">Current Image</label><div class="border rounded-3 p-2 bg-light"><img data-upload-preview="existing_image" src="" class="rounded object-fit-cover d-none" style="width:100%;max-height:180px" alt="Current image"><div data-upload-empty="existing_image" class="text-muted small">No image selected yet.</div></div></div><div class="col-12"><label class="form-label">Replace Image</label><input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div></div></div>
  <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

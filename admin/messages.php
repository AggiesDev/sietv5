<?php
$pageTitle = 'Messages';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Message marked as read.');
        header('Location: ' . SITE_URL . '/admin/messages.php');
        exit;
    }
}
require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$readFilter = in_array($_GET['read'] ?? '', ['0', '1'], true) ? $_GET['read'] : '';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
    array_push($params, ...array_fill(0, 4, '%' . $q . '%'));
}
if ($readFilter !== '') {
    $where[] = 'is_read=?';
    $params[] = (int) $readFilter;
}
$stmt = $pdo->prepare('SELECT * FROM contact_messages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC');
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>
<form class="card card-body mb-3" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-7"><label class="form-label">Search messages</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Name, email, subject, message"></div>
  <div class="col-md-2"><label class="form-label">Status</label><select name="read" class="form-select"><option value="">All</option><option value="0" <?= $readFilter==='0'?'selected':'' ?>>new</option><option value="1" <?= $readFilter==='1'?'selected':'' ?>>read</option></select></div>
  <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/messages.php">Clear</a></div>
</div></form>
<div class="table-responsive"><table class="table table-striped table-hover bg-white"><thead><tr><th>Status</th><th>From</th><th>Subject</th><th>Message</th><th></th></tr></thead><tbody>
<?php foreach ($messages as $message): ?><tr><td><?= $message['is_read'] ? '<span class="badge text-bg-secondary">read</span>' : '<span class="badge text-bg-warning">new</span>' ?></td><td><?= e($message['name']) ?><br><span class="small text-muted"><?= e($message['email']) ?></span></td><td><?= e($message['subject']) ?></td><td><?= e($message['message']) ?></td><td class="text-end"><?php if (!$message['is_read']): ?><form method="post"><input type="hidden" name="id" value="<?= (int) $message['id'] ?>"><button class="btn btn-sm btn-primary"><i class="bi bi-envelope-open"></i></button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

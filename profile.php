<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$userId = current_user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $certificate = upload_document('certificate');
        $stmt = $pdo->prepare('INSERT INTO cpd_records (user_id, activity_title, cpd_type, cpd_hours, activity_date, certificate) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, sanitize($_POST['activity_title'] ?? ''), sanitize($_POST['cpd_type'] ?? ''), (float) ($_POST['cpd_hours'] ?? 0), $_POST['activity_date'] ?? null, $certificate]);
        flash('success', 'CPD record submitted for review.');
        header('Location: ' . SITE_URL . '/profile.php');
        exit;
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
}
require_once __DIR__ . '/includes/header.php';
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch();
$stmt = $pdo->prepare('SELECT COALESCE(SUM(cpd_hours), 0) FROM cpd_records WHERE user_id = ? AND status = "approved"');
$stmt->execute([$userId]);
$hours = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT * FROM cpd_records WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$records = $stmt->fetchAll();
page_intro('My Profile', 'Membership details and CPD records.');
?>
<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card card-body">
        <h4><?= e($profile['name']) ?></h4>
        <p class="mb-1"><?= e($profile['email']) ?></p>
        <span class="badge text-bg-<?= $profile['membership_status'] === 'active' ? 'success' : 'warning' ?>"><?= e($profile['membership_status']) ?></span>
        <hr>
        <p class="mb-1"><strong>Grade:</strong> <?= e($profile['membership_grade'] ?: 'Pending') ?></p>
        <p class="mb-0"><strong>Approved CPD:</strong> <?= e((string) $hours) ?> hours</p>
      </div>
    </div>
    <div class="col-lg-8">
      <form method="post" enctype="multipart/form-data" class="card card-body needs-validation mb-4" novalidate>
        <h5>Submit CPD Record</h5>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Activity Title</label><input name="activity_title" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Type</label><input name="cpd_type" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Hours</label><input type="number" step="0.25" name="cpd_hours" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="activity_date" class="form-control" required></div>
          <div class="col-12"><label class="form-label">Certificate Document</label><input type="file" name="certificate" class="form-control" accept=".pdf,.csv,.xls,.xlsx,.jpg,.jpeg,.png,.webp"></div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Submit</button>
      </form>
      <div class="table-responsive"><table class="table table-striped table-hover bg-white"><thead><tr><th>Activity</th><th>Type</th><th>Hours</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($records as $record): ?><tr><td><?= e($record['activity_title']) ?></td><td><?= e($record['cpd_type']) ?></td><td><?= e($record['cpd_hours']) ?></td><td><?= e($record['status']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

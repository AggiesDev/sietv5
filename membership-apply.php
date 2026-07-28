<?php
$pageTitle = 'Membership Application';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO membership_applications (full_name, email, phone, grade_applied, qualification, experience) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        sanitize($_POST['full_name'] ?? ''),
        sanitize($_POST['email'] ?? ''),
        sanitize($_POST['phone'] ?? ''),
        sanitize($_POST['grade_applied'] ?? ''),
        sanitize($_POST['qualification'] ?? ''),
        sanitize($_POST['experience'] ?? ''),
    ]);
    flash('success', 'Membership application submitted.');
    header('Location: ' . SITE_URL . '/membership-apply.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
page_intro('Apply for Membership', 'Submit a placeholder application for review.');
?>
<div class="container py-5">
  <form method="post" class="card card-body needs-validation mx-auto" style="max-width:900px" novalidate>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Full Name</label><input name="full_name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Grade Applied</label><select name="grade_applied" class="form-select" required><option value="">Choose...</option><option>Student</option><option>Graduate Member</option><option>Member</option><option>Fellow</option></select></div>
      <div class="col-12"><label class="form-label">Qualification</label><textarea name="qualification" class="form-control" rows="3"></textarea></div>
      <div class="col-12"><label class="form-label">Experience</label><textarea name="experience" class="form-control" rows="4"></textarea></div>
    </div>
    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-file-earmark-check me-2"></i>Submit Application</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

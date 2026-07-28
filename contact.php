<?php
$pageTitle = 'Contact';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['subject'] ?? ''), sanitize($_POST['message'] ?? '')]);
    flash('success', 'Message received. Thank you.');
    header('Location: ' . SITE_URL . '/contact.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
page_intro('Contact', 'Send a placeholder enquiry to the SIET portal team.');
?>
<div class="container py-5">
  <form method="post" class="card card-body needs-validation mx-auto" style="max-width:820px" novalidate>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="col-12"><label class="form-label">Subject</label><input name="subject" class="form-control" required></div>
      <div class="col-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="6" required></textarea></div>
    </div>
    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-send me-2"></i>Send Message</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

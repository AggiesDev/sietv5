<?php
$pageTitle = 'Register';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 8) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, membership_status) VALUES (?, ?, ?, ?, 'public', 'pending')");
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Registration created. Please login.');
            header('Location: ' . SITE_URL . '/login.php');
            exit;
        } catch (PDOException $e) {
            flash('danger', 'Email is already registered.');
        }
    } else {
        flash('danger', 'Please provide valid details. Password must be at least 8 characters.');
    }
}
require_once __DIR__ . '/includes/header.php';
page_intro('Create Account', 'Register for access to member portal features.');
?>
<div class="container py-5">
  <form method="post" class="card card-body needs-validation mx-auto" style="max-width:720px" novalidate>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
      <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
    </div>
    <button class="btn btn-primary mt-4" type="submit"><i class="bi bi-person-check me-2"></i>Register</button>
  </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

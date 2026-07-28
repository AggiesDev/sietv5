<?php
$pageTitle = 'Login';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        header('Location: ' . (is_admin() ? SITE_URL . '/admin/index.php' : SITE_URL . '/profile.php'));
        exit;
    }
    flash('danger', 'Invalid email or password.');
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}
require_once __DIR__ . '/includes/header.php';
page_intro('Member Login', 'Access your profile, CPD records, and admin tools.');
?>
<div class="container py-5">
  <div class="row justify-content-center"><div class="col-md-6 col-lg-5">
    <form method="post" class="card card-body needs-validation" novalidate>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
      <p class="small text-muted mt-3 mb-0">Seed admin: admin@siet.org / Admin@1234</p>
    </form>
  </div></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

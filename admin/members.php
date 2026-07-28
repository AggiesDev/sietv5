<?php
$pageTitle = 'Members';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if (in_array($action, ['approved', 'rejected'], true) && $id) {
        $stmt = $pdo->prepare('UPDATE membership_applications SET status=? WHERE id=?');
        $stmt->execute([$action, $id]);
        if ($action === 'approved') {
            $app = $pdo->prepare('SELECT * FROM membership_applications WHERE id=?');
            $app->execute([$id]);
            $row = $app->fetch();
            if ($row) {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, membership_grade, membership_status, certification_status, is_directory_visible) VALUES (?, ?, ?, ?, 'member', ?, 'active', 'Pending Review', 1) ON DUPLICATE KEY UPDATE role='member', membership_grade=VALUES(membership_grade), membership_status='active', is_directory_visible=1");
                $stmt->execute([$row['full_name'], $row['email'], $row['phone'], password_hash('Member@1234', PASSWORD_DEFAULT), $row['grade_applied']]);
                $memberId = (int) $pdo->query('SELECT id FROM users WHERE email=' . $pdo->quote($row['email']))->fetchColumn();
                $stmt = $pdo->prepare("UPDATE users SET membership_no = COALESCE(membership_no, CONCAT('SIET-', LPAD(id, 5, '0'))), certification_no = COALESCE(certification_no, CONCAT('TPC-', LPAD(id * 17, 5, '0'))) WHERE id = ?");
                $stmt->execute([$memberId]);
            }
        }
        flash('success', 'Application updated.');
    } elseif ($action === 'save_member') {
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $status = in_array($_POST['membership_status'] ?? 'pending', ['active', 'pending', 'expired'], true) ? $_POST['membership_status'] : 'pending';
        $certStatus = in_array($_POST['certification_status'] ?? 'Pending Review', ['Certified', 'In Progress', 'Pending Review', 'Expired', 'Renewal Due', 'Suspended/Withdrawn'], true) ? $_POST['certification_status'] : 'Pending Review';
        try {
            $oldProfileImage = '';
            if ($memberId) {
                $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id=? AND role='member'");
                $stmt->execute([$memberId]);
                $oldProfileImage = (string) $stmt->fetchColumn();
            }
            $uploadedProfileImage = upload_image('profile_image');
            $profileImage = $uploadedProfileImage ?: sanitize($_POST['existing_profile_image'] ?? '');
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
            header('Location: ' . SITE_URL . '/admin/members.php');
            exit;
        }
        $directoryVisible = isset($_POST['is_directory_visible']) ? 1 : 0;
        if ($memberId) {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, profile_image=?, membership_grade=?, membership_status=?, membership_no=?, certification_no=?, certified_grade=?, membership_title=?, post_nominal=?, branch=?, specialisation=?, certification_status=?, is_directory_visible=?, role='member' WHERE id=?");
            $stmt->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['phone'] ?? ''), $profileImage, sanitize($_POST['membership_grade'] ?? ''), $status, sanitize($_POST['membership_no'] ?? '') ?: null, sanitize($_POST['certification_no'] ?? '') ?: null, sanitize($_POST['certified_grade'] ?? ''), sanitize($_POST['membership_title'] ?? ''), sanitize($_POST['post_nominal'] ?? ''), sanitize($_POST['branch'] ?? ''), sanitize($_POST['specialisation'] ?? ''), $certStatus, $directoryVisible, $memberId]);
            if ($uploadedProfileImage && $oldProfileImage && media_url($oldProfileImage) !== media_url($uploadedProfileImage)) {
                delete_uploaded_file($oldProfileImage);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, profile_image, password, role, membership_grade, membership_status, membership_no, certification_no, certified_grade, membership_title, post_nominal, branch, specialisation, certification_status, is_directory_visible) VALUES (?, ?, ?, ?, ?, 'member', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['email'] ?? ''), sanitize($_POST['phone'] ?? ''), $profileImage, password_hash($_POST['password'] ?: 'Member@1234', PASSWORD_DEFAULT), sanitize($_POST['membership_grade'] ?? ''), $status, sanitize($_POST['membership_no'] ?? '') ?: null, sanitize($_POST['certification_no'] ?? '') ?: null, sanitize($_POST['certified_grade'] ?? ''), sanitize($_POST['membership_title'] ?? ''), sanitize($_POST['post_nominal'] ?? ''), sanitize($_POST['branch'] ?? ''), sanitize($_POST['specialisation'] ?? ''), $certStatus, $directoryVisible]);
            $newId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare("UPDATE users SET membership_no = COALESCE(membership_no, CONCAT('SIET-', LPAD(id, 5, '0'))), certification_no = COALESCE(certification_no, CONCAT('TPC-', LPAD(id * 17, 5, '0'))) WHERE id = ?");
            $stmt->execute([$newId]);
        }
        flash('success', 'Member saved.');
    } elseif ($action === 'delete_member' && $id) {
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id=? AND role='member'");
        $stmt->execute([$id]);
        $oldProfileImage = (string) $stmt->fetchColumn();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role='member'");
        $stmt->execute([$id]);
        delete_uploaded_file($oldProfileImage);
        flash('success', 'Member deleted.');
    }
    header('Location: ' . SITE_URL . '/admin/members.php');
    exit;
}

require_once __DIR__ . '/_header.php';
$q = sanitize($_GET['q'] ?? '');
$like = '%' . $q . '%';
$appSql = 'SELECT * FROM membership_applications';
$appParams = [];
if ($q !== '') {
    $appSql .= ' WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR grade_applied LIKE ? OR status LIKE ?';
    $appParams = array_fill(0, 5, $like);
}
$appSql .= ' ORDER BY submitted_at DESC';
$stmt = $pdo->prepare($appSql);
$stmt->execute($appParams);
$apps = $stmt->fetchAll();

$memberSql = "SELECT id, name, email, phone, profile_image, membership_grade, membership_status, membership_no, certification_no, certified_grade, membership_title, post_nominal, branch, specialisation, certification_status, is_directory_visible, created_at FROM users WHERE role='member'";
$memberParams = [];
if ($q !== '') {
    $memberSql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR membership_grade LIKE ? OR membership_status LIKE ? OR membership_no LIKE ? OR certification_no LIKE ? OR certified_grade LIKE ? OR membership_title LIKE ? OR post_nominal LIKE ? OR branch LIKE ? OR specialisation LIKE ? OR certification_status LIKE ?)';
    $memberParams = array_fill(0, 13, $like);
}
$memberSql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($memberSql);
$stmt->execute($memberParams);
$members = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><h4 class="mb-0">Members</h4><p class="text-muted mb-0">Approve applications, manually manage members, or import from CSV/XLSX.</p></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-primary rounded-pill" href="<?= SITE_URL ?>/admin/import.php">Import</a><button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#memberModal"><i class="bi bi-plus-lg me-1"></i>New Member</button></div>
</div>
<form class="card card-body mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-9"><label class="form-label">Search members and applications</label><input name="q" class="form-control" value="<?= e($q) ?>" placeholder="Name, email, member no, certification no, branch, grade, status"></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button><a class="btn btn-outline-secondary" href="<?= SITE_URL ?>/admin/members.php">Clear</a></div>
  </div>
</form>
<ul class="nav nav-tabs mb-3"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#applications">Applications</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#members">Members</button></li></ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="applications"><div class="table-responsive"><table class="table table-striped table-hover bg-white align-middle"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Grade</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($apps as $app): ?><tr><td><?= e($app['full_name']) ?></td><td><?= e($app['email']) ?></td><td><?= e($app['phone']) ?></td><td><?= e($app['grade_applied']) ?></td><td><?= e($app['status']) ?></td><td class="text-end"><form method="post" class="d-inline"><input type="hidden" name="id" value="<?= (int) $app['id'] ?>"><input type="hidden" name="action" value="approved"><button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button></form> <form method="post" class="d-inline"><input type="hidden" name="id" value="<?= (int) $app['id'] ?>"><input type="hidden" name="action" value="rejected"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
  <div class="tab-pane fade" id="members"><div class="table-responsive"><table class="table table-striped table-hover bg-white align-middle"><thead><tr><th>Name</th><th>Member No</th><th>Cert No</th><th>Grade</th><th>Cert Status</th><th>Directory</th><th></th></tr></thead><tbody>
    <?php foreach ($members as $member): ?><?php $memberPayload = $member; $memberPayload['member_id'] = $member['id']; $memberPayload['profile_image'] = media_url($member['profile_image']); $memberPayload['existing_profile_image'] = media_url($member['profile_image']); ?><tr><td><img src="<?= e(media_url($member['profile_image']) ?: 'https://placehold.co/48x48?text=M') ?>" class="rounded-circle object-fit-cover me-2" style="width:36px;height:36px" alt=""><?= e($member['name']) ?><div class="small text-muted"><?= e($member['email']) ?></div></td><td><?= e($member['membership_no']) ?></td><td><?= e($member['certification_no']) ?></td><td><?= e($member['membership_grade']) ?></td><td><?= e($member['certification_status']) ?></td><td><?= $member['is_directory_visible'] ? 'Visible' : 'Hidden' ?></td><td class="text-end"><button class="btn btn-sm btn-outline-primary edit-item" data-bs-toggle="modal" data-bs-target="#memberModal" data-item='<?= e(json_encode($memberPayload)) ?>'><i class="bi bi-pencil"></i></button> <form method="post" class="d-inline" onsubmit="return confirm('Delete this member?')"><input type="hidden" name="action" value="delete_member"><input type="hidden" name="id" value="<?= (int) $member['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
</div>

<div class="modal fade" id="memberModal"><div class="modal-dialog modal-xl"><form method="post" enctype="multipart/form-data" class="modal-content needs-validation" novalidate>
  <div class="modal-header"><h5 class="modal-title">Member</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="action" value="save_member"><input type="hidden" name="member_id"><input type="hidden" name="existing_profile_image"><div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Current Profile Image</label><div class="border rounded-3 p-2 bg-light"><img data-upload-preview="existing_profile_image" src="" class="rounded-circle object-fit-cover d-none" style="width:96px;height:96px" alt="Current profile image"><div data-upload-empty="existing_profile_image" class="text-muted small">No image selected yet.</div></div></div>
    <div class="col-md-6"><label class="form-label">Replace Profile Image</label><input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div>
    <div class="col-md-6"><label class="form-label">Membership No.</label><input name="membership_no" class="form-control" placeholder="SIET-00001"></div>
    <div class="col-md-6"><label class="form-label">Certification No.</label><input name="certification_no" class="form-control" placeholder="TPC-00001"></div>
    <div class="col-md-6"><label class="form-label">Membership Grade</label><input name="membership_grade" class="form-control" placeholder="Member"></div>
    <div class="col-md-6"><label class="form-label">Certification Level</label><input name="certified_grade" class="form-control" placeholder="Certified Engineering Technologist"></div>
    <div class="col-md-6"><label class="form-label">Membership Title</label><input name="membership_title" class="form-control" placeholder="Graduate Member Title"></div>
    <div class="col-md-6"><label class="form-label">Certification Post-nominal</label><input name="post_nominal" class="form-control" placeholder="MSIET"></div>
    <div class="col-md-6"><label class="form-label">Branch of Engineering</label><input name="branch" class="form-control" placeholder="Mechanical"></div>
    <div class="col-md-6"><label class="form-label">Specialisation</label><input name="specialisation" class="form-control" placeholder="Industrial Automation"></div>
    <div class="col-md-6"><label class="form-label">Membership Status</label><select name="membership_status" class="form-select"><option value="active">active</option><option value="pending">pending</option><option value="expired">expired</option></select></div>
    <div class="col-md-6"><label class="form-label">Certification Status</label><select name="certification_status" class="form-select"><option>Certified</option><option>In Progress</option><option>Pending Review</option><option>Expired</option><option>Renewal Due</option><option>Suspended/Withdrawn</option></select></div>
    <div class="col-md-6"><label class="form-label">Password for new member</label><input type="password" name="password" class="form-control" placeholder="Default: Member@1234"></div>
    <div class="col-md-6 d-flex align-items-end"><label class="form-check mb-2"><input type="checkbox" name="is_directory_visible" value="1" class="form-check-input" checked> Show on public directory</label></div>
  </div></div>
  <div class="modal-footer"><button class="btn btn-primary rounded-pill px-4">Save</button></div>
</form></div></div>
<?php require_once __DIR__ . '/_footer.php'; ?>

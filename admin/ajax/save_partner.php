<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
try {
    $id = (int) ($_POST['id'] ?? 0);
    $oldLogo = '';
    if ($id) {
        $stmt = $pdo->prepare('SELECT logo FROM partners WHERE id=?');
        $stmt->execute([$id]);
        $oldLogo = (string) $stmt->fetchColumn();
    }
    $uploadedLogo = upload_image('logo');
    $logo = $uploadedLogo ?: sanitize($_POST['existing_logo'] ?? '');
    $type = in_array($_POST['type'] ?? 'local', ['local', 'international', 'organisational'], true) ? $_POST['type'] : 'local';
    $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
    if ($id) {
        $stmt = $pdo->prepare('UPDATE partners SET name=?, type=?, description=?, website=?, logo=?, status=? WHERE id=?');
        $stmt->execute([sanitize($_POST['name'] ?? ''), $type, sanitize($_POST['description'] ?? ''), sanitize($_POST['website'] ?? ''), $logo, $status, $id]);
        if ($uploadedLogo && $oldLogo && media_url($oldLogo) !== media_url($uploadedLogo)) {
            delete_uploaded_file($oldLogo);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO partners (name, type, description, website, logo, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([sanitize($_POST['name'] ?? ''), $type, sanitize($_POST['description'] ?? ''), sanitize($_POST['website'] ?? ''), $logo, $status]);
    }
    flash('success', 'Partner saved.');
} catch (Throwable $e) { flash('danger', $e->getMessage()); }
header('Location: ' . SITE_URL . '/admin/partners.php');
exit;
?>

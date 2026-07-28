<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
try {
    $id = (int) ($_POST['id'] ?? 0);
    $oldImage = '';
    if ($id) {
        $stmt = $pdo->prepare('SELECT image FROM council WHERE id=?');
        $stmt->execute([$id]);
        $oldImage = (string) $stmt->fetchColumn();
    }
    $uploadedImage = upload_image('image');
    $image = $uploadedImage ?: sanitize($_POST['existing_image'] ?? '');
    if ($id) {
        $stmt = $pdo->prepare('UPDATE council SET name=?, position=?, period=?, image=?, bio=?, display_order=? WHERE id=?');
        $stmt->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['position'] ?? ''), sanitize($_POST['period'] ?? ''), $image, sanitize($_POST['bio'] ?? ''), (int) ($_POST['display_order'] ?? 0), $id]);
        if ($uploadedImage && $oldImage && media_url($oldImage) !== media_url($uploadedImage)) {
            delete_uploaded_file($oldImage);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO council (name, position, period, image, bio, display_order) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([sanitize($_POST['name'] ?? ''), sanitize($_POST['position'] ?? ''), sanitize($_POST['period'] ?? ''), $image, sanitize($_POST['bio'] ?? ''), (int) ($_POST['display_order'] ?? 0)]);
    }
    flash('success', 'Council member saved.');
} catch (Throwable $e) { flash('danger', $e->getMessage()); }
header('Location: ' . SITE_URL . '/admin/council.php');
exit;
?>

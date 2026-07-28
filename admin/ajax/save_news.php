<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
try {
    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $oldImage = '';
    if ($id) {
        $stmt = $pdo->prepare('SELECT image FROM news WHERE id=?');
        $stmt->execute([$id]);
        $oldImage = (string) $stmt->fetchColumn();
    }
    $uploadedImage = upload_image('image');
    $image = $uploadedImage ?: sanitize($_POST['existing_image'] ?? '');
    $status = in_array($_POST['status'] ?? 'draft', ['published', 'draft'], true) ? $_POST['status'] : 'draft';
    $slug = unique_slug($pdo, 'news', $title, $id ?: null);
    if ($id) {
        $stmt = $pdo->prepare('UPDATE news SET title=?, slug=?, excerpt=?, content=?, image=?, status=? WHERE id=?');
        $stmt->execute([$title, $slug, sanitize($_POST['excerpt'] ?? ''), sanitize($_POST['content'] ?? ''), $image, $status, $id]);
        if ($uploadedImage && $oldImage && media_url($oldImage) !== media_url($uploadedImage)) {
            delete_uploaded_file($oldImage);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO news (title, slug, excerpt, content, image, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $slug, sanitize($_POST['excerpt'] ?? ''), sanitize($_POST['content'] ?? ''), $image, $status, current_user()['id']]);
    }
    flash('success', 'News saved.');
} catch (Throwable $e) {
    flash('danger', $e->getMessage());
}
header('Location: ' . SITE_URL . '/admin/news.php');
exit;
?>

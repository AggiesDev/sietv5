<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$allowed = ['news', 'events', 'partners', 'council'];
$table = $_POST['table'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
if (in_array($table, $allowed, true) && $id) {
    $mediaColumns = [
        'news' => 'image',
        'events' => 'image',
        'partners' => 'logo',
        'council' => 'image',
    ];
    $mediaValue = '';
    if (isset($mediaColumns[$table])) {
        $column = $mediaColumns[$table];
        $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $mediaValue = (string) $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    delete_uploaded_file($mediaValue);
    flash('success', 'Item deleted.');
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/index.php'));
exit;
?>

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
        $stmt = $pdo->prepare('SELECT image FROM events WHERE id=?');
        $stmt->execute([$id]);
        $oldImage = (string) $stmt->fetchColumn();
    }
    $uploadedImage = upload_image('image');
    $image = $uploadedImage ?: sanitize($_POST['existing_image'] ?? '');
    $status = in_array($_POST['status'] ?? 'upcoming', ['upcoming', 'ongoing', 'completed'], true) ? $_POST['status'] : 'upcoming';
    $slug = unique_slug($pdo, 'events', $title, $id ?: null);
    if ($id) {
        $stmt = $pdo->prepare('UPDATE events SET title=?, slug=?, description=?, event_date=?, event_time=?, location=?, image=?, status=?, max_participants=? WHERE id=?');
        $stmt->execute([$title, $slug, sanitize($_POST['description'] ?? ''), $_POST['event_date'] ?? null, $_POST['event_time'] ?? null, sanitize($_POST['location'] ?? ''), $image, $status, (int) ($_POST['max_participants'] ?? 0), $id]);
        if ($uploadedImage && $oldImage && media_url($oldImage) !== media_url($uploadedImage)) {
            delete_uploaded_file($oldImage);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO events (title, slug, description, event_date, event_time, location, image, status, max_participants, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $slug, sanitize($_POST['description'] ?? ''), $_POST['event_date'] ?? null, $_POST['event_time'] ?? null, sanitize($_POST['location'] ?? ''), $image, $status, (int) ($_POST['max_participants'] ?? 0), current_user()['id']]);
    }
    flash('success', 'Event saved.');
} catch (Throwable $e) { flash('danger', $e->getMessage()); }
header('Location: ' . SITE_URL . '/admin/events.php');
exit;
?>

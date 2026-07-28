<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
session_destroy();
header('Location: ' . SITE_URL . '/login.php');
exit;
?>

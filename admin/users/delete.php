<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: /login.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userId = $_GET['id'] ?? 0;

// Don't allow deleting yourself
if ($userId == $_SESSION['user_id']) {
    header("Location: /admin/dashboard.php");
    exit();
}

// Delete user
$db->query("DELETE FROM user_website_access WHERE user_id = $userId");
$db->query("DELETE FROM users WHERE id = $userId");

header("Location: /admin/dashboard.php");
exit();

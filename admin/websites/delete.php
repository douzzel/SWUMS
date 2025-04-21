<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: /login.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$websiteId = $_GET['id'] ?? 0;

// Check if website exists
$stmt = $db->prepare("SELECT id FROM websites WHERE id = ?");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // Delete associated records first
    $db->query("DELETE FROM status_checks WHERE website_id = $websiteId");
    $db->query("DELETE FROM outages WHERE website_id = $websiteId");
    $db->query("DELETE FROM user_website_access WHERE website_id = $websiteId");

    // Then delete the website
    $db->query("DELETE FROM websites WHERE id = $websiteId");
}

header("Location: /admin/dashboard.php");
exit();

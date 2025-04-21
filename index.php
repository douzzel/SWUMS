<?php
require_once 'includes/config.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header("Location: " . ($auth->isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
} else {
    header("Location: login.php");
    exit();
}

<?php

require_once "includes/config.php";
require_once "includes/common.php";

// Check if already installed
$tablesExist = false;
$db = new PDO($dsn, $username, $password, $options);
try {
    $result = $db->query("SELECT 1 FROM users LIMIT 1");
    $tablesExist = $result !== false;
} catch (Exception $e) {
    $tablesExist = false;
}

if ($tablesExist) {
    header("Location: login.php");
    exit();
} else {
    header("Location: install.php");
    exit();
}

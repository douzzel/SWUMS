<?php
require_once 'includes/config.php';

$auth = new Auth();
$auth->logout();

header("Location: login.php");
exit();

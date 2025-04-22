<?php

// PDO :
$host       = "sql307.infinityfree.com";
$username   = "if0_38639878";
$password   = "GuE3NI4tkoeD";
$dbname     = "if0_38639878_monitor";
$dbcharset  = "utf8";
$dsn        = "mysql:host=$host;dbname=$dbname;charset=$dbcharset";
$options    = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
);

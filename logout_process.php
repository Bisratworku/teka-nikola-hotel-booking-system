<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_handler.php';

$handler = new DbSessionHandler($pdo);
session_set_save_handler($handler, true);
session_start();
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;

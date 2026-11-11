<?php
require_once __DIR__ . '/config.php';

try {
  $pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException $e) {
  // In a real application, you would log this error
  // error_log("DB Connection failed: " . $e->getMessage());
  header("Location: error.php");
  exit;
}


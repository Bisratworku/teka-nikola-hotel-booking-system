<?php
require 'db.php';
require 'auth.php';
require_login();

// Only admin can delete users
if ($_SESSION['user']['role'] !== 'admin') {
  header('Location: index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($user_id > 0) {
      // Prevent deleting yourself
      if ($user_id === (int)$_SESSION['user']['id']) {
        // Ideally, show a proper error page
        http_response_code(403);
        die("You cannot delete your own account.");
      }

      $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $stmt->execute([$user_id]);
    }
}

header("Location: admin_users.php");
exit;

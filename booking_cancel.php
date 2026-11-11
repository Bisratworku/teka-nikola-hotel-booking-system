<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'auth.php';
require_login();

if (!in_array($_SESSION['user']['role'], ['admin','staff'])) {
  header("Location: index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($bookingId > 0) {
      $cancel = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=? LIMIT 1");
      $cancel->execute([$bookingId]);
    }
}

$redirect_to = $_SERVER['HTTP_REFERER'] ?? 'staff_dashboard.php';
header("Location: $redirect_to");
exit;

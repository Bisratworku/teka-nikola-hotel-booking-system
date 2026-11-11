<?php
require 'db.php';
require 'auth.php';
require_once __DIR__ . '/audit_log.php';

require_login();
require_role(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}

verify_csrf_token();

$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

$allowed_actions = ['delete', 'checkin', 'checkout'];

if ($booking_id > 0 && in_array($action, $allowed_actions)) {
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        audit_log_current_user($pdo, 'booking_deleted', "Deleted booking #{$booking_id}");
    } elseif ($action === 'checkin' || $action === 'checkout') {
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $current_status = $stmt->fetchColumn();

        if ($action === 'checkin' && $current_status === 'confirmed') {
            $update = $pdo->prepare("UPDATE bookings SET status = 'checked_in', actual_check_in = NOW() WHERE id = ?");
            $update->execute([$booking_id]);
            audit_log_current_user($pdo, 'guest_checked_in', "Checked in guest for booking #{$booking_id}");
        } elseif ($action === 'checkout' && $current_status === 'checked_in') {
            $update = $pdo->prepare("UPDATE bookings SET status = 'checked_out', actual_check_out = NOW() WHERE id = ?");
            $update->execute([$booking_id]);
            audit_log_current_user($pdo, 'guest_checked_out', "Checked out guest for booking #{$booking_id}");
        }
    }
}

$redirect_to = $_SERVER['HTTP_REFERER'] ?? 'staff_dashboard.php';
if (isset($action)) {
    if ($action === 'checkin') {
        $sep = (strpos($redirect_to, '?') === false) ? '?' : '&';
        $redirect_to .= $sep . 'checkin_success=1';
    } elseif ($action === 'checkout' && isset($booking_id) && $booking_id > 0) {
        $redirect_to = 'staff_checkin_queue.php?invoice_id=' . $booking_id;
    }
}
header("Location: $redirect_to");
exit;
?>

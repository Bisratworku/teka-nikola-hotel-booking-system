<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit_log.php';

$can_process_booking = true;
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
} else {
    $user_role = $_SESSION['user']['role'] ?? '';
    if ($user_role !== 'customer') {
        $errors[] = 'Due to hotel policy, admin and staff are required to create a customer account to make reservations.';
        $can_process_booking = false;
    }
}

require 'header.php';

function h($s) {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_process_booking) {
  verify_csrf_token();
  $room_id  = $_POST['room_id'] ?? '';
  $ci       = $_POST['ci'] ?? $_POST['check_in'] ?? '';
  $co       = $_POST['co'] ?? $_POST['check_out'] ?? '';
  $guests   = (int)($_POST['guests'] ?? 1);
  $user_id  = $_SESSION['user']['id'];

  // --- Validate inputs ---
  if (!$room_id || !$ci || !$co) {
    $errors[] = 'All fields are required.';
  } elseif (strtotime($co) <= strtotime($ci)) {
    $errors[] = 'Check-out date must be after check-in date.';
  }

  // --- Transactional booking ---
  if (empty($errors)) {
    try {
        $pdo->beginTransaction();

        $room_stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=? AND is_active=1 LIMIT 1");
        $room_stmt->execute([$room_id]);
        $room = $room_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            $errors[] = 'Room not found.';
        } elseif ($guests > (int)$room['max_guests']) {
            $errors[] = 'Too many guests for selected room.';
        } else {
            $inventory = max(1, (int)$room['inventory']);

            // Advanced reservation rules
            $rule = $pdo->query("SELECT * FROM reservation_rules ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $nights = max(1, (int)((strtotime($co) - strtotime($ci)) / 86400));
            $today = new DateTime('now', new DateTimeZone('UTC'));
            $advance_days = max(0, (int)((strtotime($ci) - $today->getTimestamp()) / 86400));

            if ($rule) {
                if ($nights < (int)$rule['min_nights']) {
                    $errors[] = "Minimum stay is {$rule['min_nights']} nights.";
                }
                if ($nights > (int)$rule['max_nights']) {
                    $errors[] = "Maximum stay is {$rule['max_nights']} nights.";
                }
                if ($advance_days < (int)$rule['min_advance_days']) {
                    $errors[] = "Bookings must be made at least {$rule['min_advance_days']} days in advance.";
                }
                if ($advance_days > (int)$rule['max_advance_days']) {
                    $errors[] = "Bookings can be made no more than {$rule['max_advance_days']} days in advance.";
                }
                if (!empty($rule['max_guests']) && $guests > (int)$rule['max_guests']) {
                    $errors[] = "Only up to {$rule['max_guests']} guests allowed per booking.";
                }
            }

            // Check overlapping bookings with a lock
            $conflict = $pdo->prepare("
                SELECT COUNT(*) FROM bookings
                WHERE room_id = ? AND status = 'confirmed'
                AND NOT (check_out <= ? OR check_in >= ?) FOR UPDATE
            ");
            $conflict->execute([$room_id, $ci, $co]);
            $used = (int)$conflict->fetchColumn();

            if ($used >= $inventory) {
                $errors[] = t('room_fully_booked');
            }
        }

        if (empty($errors)) {
            // Create booking record
            $booking_stmt = $pdo->prepare("
                INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())
            ");
            $booking_stmt->execute([$user_id, $room_id, $ci, $co, $guests]);
            $booking_id = $pdo->lastInsertId();
            
            // Log booking creation
            audit_log_current_user($pdo, 'booking_created', "New booking #{$booking_id} for room #{$room_id}");
            
            // Save selected services
            if (!empty($_POST['services']) && is_array($_POST['services'])) {
                $stmt = $pdo->prepare("INSERT INTO Services_in_Booking (booking_id, service_id) VALUES (?, ?)");
                foreach ($_POST['services'] as $service_id) {
                    $stmt->execute([$booking_id, (int)$service_id]);
                }
            }
            
            $pdo->commit();
            $success = true;
        } else {
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $errors[] = 'An unexpected error occurred. Please try again.';
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
  }
}



?>

<section class="container">
  <h1 class="h2">Booking Confirmation</h1>

  <?php if ($success): ?>
    <div class="card" style="padding:20px; max-width: 700px; margin: 0 auto;">
        <h3 style="margin-top:0;">✅ Your booking is confirmed!</h3>
        <p>Your reservation is now confirmed. You can view it under <a href="my_bookings.php">My Bookings</a>.</p>
        <hr>
        <p><strong>Confirmation ID:</strong> #<?= htmlspecialchars($booking_id) ?></p>
        <p><strong>Check-in Time:</strong> 2:00 PM</p>
        <p><strong>Check-out Time:</strong> 12:00 PM</p>
        <hr>
        <p class="muted">Please be ready to show your confirmation ID to the receptionist when you arrive at the hotel.</p>
    </div>
  <?php else: ?>
    <div class="error">
      <?php foreach ($errors as $e): ?>
        <p>❌ <?= h($e) ?></p>
      <?php endforeach; ?>
    </div>
    <a class="btn" href="javascript:history.back()">⬅ Go Back</a>
  <?php endif; ?>
</section>

<?php require 'footer.php'; ?>

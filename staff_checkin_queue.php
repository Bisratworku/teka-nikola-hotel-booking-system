<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit_log.php';

require_login();
require_role(['admin', 'staff']);

$message = '';
$invoice_booking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($booking_id > 0 && in_array($action, ['checkin', 'checkout'], true)) {
        if ($action === 'checkin') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in', actual_check_in = NOW() WHERE id = ? AND status = 'confirmed'");
            $stmt->execute([$booking_id]);
            if ($stmt->rowCount() > 0) {
                audit_log_current_user($pdo, 'guest_checked_in', "Checked in guest for booking #{$booking_id}");
                header('Location: staff_checkin_queue.php?checkin_success=1');
            }
            $message = 'Unable to check in. Please verify booking status.';
        } elseif ($action === 'checkout') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_out', actual_check_out = NOW() WHERE id = ? AND status = 'checked_in'");
            $stmt->execute([$booking_id]);
            if ($stmt->rowCount() > 0) {
                audit_log_current_user($pdo, 'guest_checked_out', "Checked out guest for booking #{$booking_id}");
                header('Location: staff_checkin_queue.php?invoice_id=' . $booking_id);
            }
            $message = 'Unable to check out. Please verify booking status.';
        }
    }
}

if (isset($_GET['invoice_id']) && ctype_digit($_GET['invoice_id'])) {
    $invoice_id = (int)$_GET['invoice_id'];
    $stmt = $pdo->prepare("SELECT b.*, u.name AS guest_name, u.email AS guest_email, r.type AS room_type, r.number AS room_number, r.rate_cents
                          FROM bookings b
                          JOIN users u ON u.id = b.user_id
                          JOIN rooms r ON r.id = b.room_id
                          WHERE b.id = ?");
    $stmt->execute([$invoice_id]);
    $invoice_booking = $stmt->fetch(PDO::FETCH_ASSOC);
}

$pendingBookings = $pdo->query("SELECT b.id, b.check_in, b.check_out, b.status, u.name AS guest_name, u.email AS guest_email, r.type AS room_type, r.number AS room_number
                               FROM bookings b
                               JOIN users u ON u.id = b.user_id
                               JOIN rooms r ON r.id = b.room_id
                               WHERE b.status IN ('confirmed', 'checked_in')
                               ORDER BY b.check_in ASC, b.created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

require 'staff_header.php';
?>

<section class="container">
  <style>
    @media print {
      body * { visibility: hidden; }
      #invoice, #invoice * { visibility: visible; }
      #invoice { position: absolute; top: 0; left: 0; width: 100%; }
      #invoice table { width: 100%; }
    }
  </style>

  <h2>Check-in Queue</h2>

  <?php if (!empty($_GET['checkin_success'])): ?>
    <div class="card" style="border:1px solid #0f5132; background:#d1e7dd; color:#0f5132; margin-bottom:12px; padding:12px;">
      ✓ Check-in recorded successfully. Booking is now in checked-in state.
    </div>
  <?php endif; ?>

  <?php if ($message): ?>
    <div class="card" style="border:1px solid #842029; background:#f8d7da; color:#842029; margin-bottom:12px; padding:12px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:20px">
    <p>Guests with confirmed bookings appear here for check-in and check-out.</p>
    <table class="table" style="width:100%;">
      <thead>
        <tr>
          <th>Booking</th>
          <th>Guest</th>
          <th>Room</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($pendingBookings): foreach ($pendingBookings as $b): ?>
          <tr>
            <td>#<?= (int)$b['id'] ?></td>
            <td><?= htmlspecialchars($b['guest_name']) ?><br><small><?= htmlspecialchars($b['guest_email']) ?></small></td>
            <td><?= htmlspecialchars($b['room_type']) ?> #<?= htmlspecialchars($b['room_number']) ?></td>
            <td><?= htmlspecialchars($b['check_in']) ?></td>
            <td><?= htmlspecialchars($b['check_out']) ?></td>
            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['status']))) ?></td>
            <td>
              <?php if ($b['status'] === 'confirmed'): ?>
                <form method="post" style="margin:0;" onsubmit="return confirm('Check in this guest now?');">
                  <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                  <input type="hidden" name="action" value="checkin">
                  <?php csrf_input(); ?>
                  <button class="btn primary small" type="submit">Check-in</button>
                </form>
              <?php elseif ($b['status'] === 'checked_in'): ?>
                <form method="post" style="margin:0;" onsubmit="return confirm('Check out this guest and generate invoice?');">
                  <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                  <input type="hidden" name="action" value="checkout">
                  <?php csrf_input(); ?>
                  <button class="btn" type="submit">Check-out</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" class="muted">No bookings are pending check-in or check-out.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($invoice_booking): ?>
  <?php
    $nights = max(1, (int) ((strtotime($invoice_booking['check_out']) - strtotime($invoice_booking['check_in'])) / 86400));
    $rate = ((int)$invoice_booking['rate_cents']) / 100.0;
    $room_total = $rate * $nights;
    $tax = round($room_total * 0.1, 2);
    $total = round($room_total + $tax, 2);
  ?>

  <div class="card" style="margin-bottom:20px" id="invoice">
    <h3>Invoice for Booking #<?= (int)$invoice_booking['id'] ?></h3>
    <p><strong>Guest:</strong> <?= htmlspecialchars($invoice_booking['guest_name']) ?> (<?= htmlspecialchars($invoice_booking['guest_email']) ?>)</p>
    <p><strong>Room:</strong> <?= htmlspecialchars($invoice_booking['room_type']) ?> #<?= htmlspecialchars($invoice_booking['room_number']) ?></p>
    <p><strong>Check-in:</strong> <?= htmlspecialchars($invoice_booking['check_in']) ?> &nbsp; <strong>Check-out:</strong> <?= htmlspecialchars($invoice_booking['check_out']) ?></p>
    <p><strong>Actual Check-in:</strong> <?= htmlspecialchars($invoice_booking['actual_check_in'] ?? 'N/A') ?> &nbsp; <strong>Actual Check-out:</strong> <?= htmlspecialchars($invoice_booking['actual_check_out'] ?? 'N/A') ?></p>

    <table class="table" style="max-width:480px;">
      <tr><td>Nights</td><td><?= $nights ?></td></tr>
      <tr><td>Rate per night</td><td>$<?= number_format($rate, 2) ?></td></tr>
      <tr><td>Room total</td><td>$<?= number_format($room_total, 2) ?></td></tr>
      <tr><td>Tax (10%)</td><td>$<?= number_format($tax, 2) ?></td></tr>
      <tr><td><strong>Total</strong></td><td><strong>$<?= number_format($total, 2) ?></strong></td></tr>
    </table>

    <button class="btn" onclick="window.print()">Print Invoice</button>
  </div>
  <?php endif; ?>
</section>

<?php require 'staff_footer.php'; ?>

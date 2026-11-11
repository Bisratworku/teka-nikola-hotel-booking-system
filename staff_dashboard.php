<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'staff_header.php';
require_once __DIR__ . '/audit_log.php';

require_login();
require_role(['admin', 'staff']); // ✅ allows either role

// Fetch summary counts
$roomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$bookingsCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$upcomingCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE check_in >= CURDATE() AND status != 'cancelled'")->fetchColumn();
$customersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

// Fetch recent confirmed bookings not yet checked in (for receptionist pending check-in)
$recent = $pdo->query("
  SELECT b.*, u.name, u.email, r.type, r.number
  FROM bookings b
  JOIN users u ON b.user_id = u.id
  JOIN rooms r ON b.room_id = r.id
  WHERE b.status = 'confirmed'
  ORDER BY b.check_in ASC, b.created_at DESC
  LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch queue of bookings that are current and need checkin / checkout
$pendingBookings = $pdo->query("SELECT b.id, b.check_in, b.check_out, b.status, u.name, u.email, r.type, r.number
                               FROM bookings b
                               JOIN users u ON u.id = b.user_id
                               JOIN rooms r ON r.id = b.room_id
                               WHERE b.status IN ('confirmed', 'checked_in')
                               ORDER BY b.check_in ASC, b.created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="container">
  <h2>Staff Dashboard</h2>

  <?php if (isset($_GET['checkin_success'])): ?>
    <div class="card" style="border:1px solid #0f5132; background:#d1e7dd; color:#0f5132; margin-bottom:12px; padding:12px;">
      ✓ Check-in recorded successfully. This booking has been removed from the pending check-in list.
    </div>
  <?php endif; ?>

  
  
  <div class="card">
    <h3>Check-in Queue</h3>
    <p>Bookings in confirmed or checked-in status appear here for staff action.</p>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
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
            <td><?= (int)$b['id'] ?></td>
            <td>
              <?= htmlspecialchars($b['name']) ?><br>
              <span class="muted small"><?= htmlspecialchars($b['email']) ?></span>
            </td>
            <td><?= htmlspecialchars($b['type']) ?> • <?= htmlspecialchars($b['number']) ?></td>
            <td><?= htmlspecialchars($b['check_in']) ?></td>
            <td><?= htmlspecialchars($b['check_out']) ?></td>
            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $b['status']))) ?></td>
            <td>
              <?php if ($b['status'] === 'confirmed'): ?>
                <form method="post" action="admin_booking_action.php" onsubmit="return confirm('Check in this guest now?');" style="display:inline-block; margin:0;">
                  <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                  <input type="hidden" name="action" value="checkin">
                  <?php csrf_input(); ?>
                  <button class="btn primary small" type="submit">Check-in</button>
                </form>
              <?php elseif ($b['status'] === 'checked_in'): ?>
                <form method="post" action="admin_booking_action.php" onsubmit="return confirm('Check out this guest and generate checkout?');" style="display:inline-block; margin:0;">
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

  
</section>

<?php require 'staff_footer.php'; ?>

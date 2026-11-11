<?php
// manage_rooms.php — Full Admin control of Rooms + User Bookings
require_once __DIR__.'/db.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/audit_log.php';
require_role(['admin','staff']);
require_once __DIR__.'/admin_header.php';

// Helper functions
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function money($cents){ return '$'.number_format($cents/100, 2); }



// Fetch all bookings with user and room info
$sql = "
  SELECT b.id AS booking_id, b.status, b.check_in, b.check_out, b.created_at, 
         u.name AS user_name, u.email AS user_email, 
         r.type AS room_type, r.number AS room_number, r.rate_cents, r.floor
  FROM bookings b
  JOIN users u ON b.user_id = u.id
  JOIN rooms r ON b.room_id = r.id
  ORDER BY b.created_at DESC
";
$bookings = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch rooms
$rooms = $pdo->query("SELECT id, type, number, inventory, rate_cents, max_guests, is_active FROM rooms ORDER BY type, number")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="container">
  <h2 class="h2" style="margin-bottom:10px">🏨 Rooms Overview</h2>
  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Room #</th>
          <th>Type</th>
          <th>Rate</th>
          <th>Max Guests</th>
          <th>Inventory</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
        <tr>
          <td><?= (int)$r['number'] ?></td>
          <td><?= h($r['type']) ?></td>
          <td><?= money($r['rate_cents']) ?></td>
          <td><?= (int)$r['max_guests'] ?></td>
          <td><?= (int)$r['inventory'] ?></td>
          <td><?= $r['is_active'] ? '✅ Active' : '❌ Inactive' ?></td>
          <td><a class="btn tiny" href="room_edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h2 class="h2" style="margin:30px 0 10px">📅 All Bookings</h2>
  <p class="muted" style="margin-bottom:10px">View all user bookings, their dates, and manage them.</p>
  <div class="card" style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>User</th>
          <th>Email</th>
          <th>Room</th>
          <th>Type</th>
          <th>Check-In</th>
          <th>Check-Out</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td>#<?= (int)$b['booking_id'] ?></td>
            <td><?= h($b['user_name']) ?></td>
            <td><?= h($b['user_email']) ?></td>
            <td><?= (int)$b['room_number'] ?></td>
            <td><?= h($b['room_type']) ?></td>
            <td><?= h($b['check_in']) ?></td>
            <td><?= h($b['check_out']) ?></td>
            <td>
              <?php if ($b['status']==='cancelled'): ?>
                <span class="chip" style="background:#ffe5e5;border-color:#f0bcbc">Cancelled</span>
              <?php else: ?>
                <span class="chip" style="background:#e6ffe6;border-color:#9fe69f">Confirmed</span>
              <?php endif; ?>
            </td>
            <td class="tiny"><?= h($b['created_at']) ?></td>
            <td>
              <?php if ($b['status'] === 'confirmed'): ?>
                <form method="post" action="admin_booking_action.php" style="display:inline" onsubmit="return confirm('Check in this guest now?');">
                  <?php csrf_input(); ?>
                  <input type="hidden" name="action" value="checkin">
                  <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                  <button class="btn tiny">Check-in</button>
                </form>
              <?php elseif ($b['status'] === 'checked_in'): ?>
                <form method="post" action="admin_booking_action.php" style="display:inline" onsubmit="return confirm('Check out this guest now?');">
                  <?php csrf_input(); ?>
                  <input type="hidden" name="action" value="checkout">
                  <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                  <button class="btn tiny">Check-out</button>
                </form>
              <?php else: ?>
                <span class="muted">No actions</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?>
          <tr><td colspan="10" class="muted">No bookings found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__.'/admin_footer.php'; ?>

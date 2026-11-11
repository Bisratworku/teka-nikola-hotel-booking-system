<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'auth.php';

require_login();
require_role(['admin']); // Only admins can access this page

// Fetch summary counts
$roomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$bookingsCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$upcomingCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE check_in >= CURDATE() AND status != 'cancelled'")->fetchColumn();
$customersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$staffCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();

// Fetch recent bookings
$recent = $pdo->query("
  SELECT b.*, u.name, u.email, r.type, r.number
  FROM bookings b
  JOIN users u ON b.user_id = u.id
  JOIN rooms r ON b.room_id = r.id
  ORDER BY b.created_at DESC
  LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require 'admin_header.php'; ?>

<section class="container">
  <h2>Admin Dashboard</h2>

  <div class="grid" style="margin-top:10px;margin-bottom:20px">
    <div class="card center"><div class="h1"><?= (int)$roomsCount ?></div><div class="muted">Rooms</div></div>
    <div class="card center"><div class="h1"><?= (int)$bookingsCount ?></div><div class="muted">Bookings</div></div>
    <div class="card center"><div class="h1"><?= (int)$upcomingCount ?></div><div class="muted">Upcoming</div></div>
    <div class="card center"><div class="h1"><?= (int)$customersCount ?></div><div class="muted">Customers</div></div>
    <div class="card center"><div class="h1"><?= (int)$staffCount ?></div><div class="muted">Staff</div></div>
  </div>

  <div class="card" style="margin-bottom:20px">
    <h3><?= htmlspecialchars(t('quick_actions')) ?></h3>
    <div class="flex" style="gap:10px">
      <a class="btn" href="manage_rooms.php"><?= htmlspecialchars(t('rooms')) ?></a>
      <a class="btn" href="admin_users.php"><?= htmlspecialchars(t('users')) ?></a>
      <a class="btn" href="admin_audit_log.php"><?= htmlspecialchars(t('audit_log')) ?></a>
      <a class="btn" href="admin_report_charts.php"><?= htmlspecialchars(t('report_charts')) ?></a>
      <a class="btn" href="admin_rules.php"><?= htmlspecialchars(t('reservation_rules')) ?></a>
      <a class="btn primary" href="admin_user_add.php">Add Receptionist/Admin</a>
    </div>
  </div>

  <div class="card">
    <h3>Recent Bookings</h3>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Guest</th>
          <th>Room</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td>
              <?= htmlspecialchars($r['name']) ?><br>
              <span class="muted small"><?= htmlspecialchars($r['email']) ?></span>
            </td>
            <td><?= htmlspecialchars($r['type']) ?> • <?= htmlspecialchars($r['number']) ?></td>
            <td><?= htmlspecialchars($r['check_in']) ?></td>
            <td><?= htmlspecialchars($r['check_out']) ?></td>
            <td>
              <?php 
              $status = htmlspecialchars($r['status']);
              if ($status === 'checked_in') {
                echo '<span class="chip" style="background:#e6ffe6;border-color:#9fe69f">Checked-in</span>';
              } elseif ($status === 'checked_out') {
                echo '<span class="chip" style="background:#ffe5e5;border-color:#f0bcbc">Checked-out</span>';
              } elseif ($status === 'confirmed') {
                echo '<span class="chip" style="background:#fff3cd;border-color:#ffeaa7">Confirmed</span>';
              } elseif ($status === 'pending') {
                echo '<span class="chip" style="background:#e2e3e5;border-color:#ced4da">Pending</span>';
              } elseif ($status === 'cancelled') {
                echo '<span class="chip" style="background:#f8d7da;border-color:#f5c6cb">Cancelled</span>';
              } else {
                echo '<span class="chip">' . ucfirst($status) . '</span>';
              }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require 'admin_footer.php'; ?>

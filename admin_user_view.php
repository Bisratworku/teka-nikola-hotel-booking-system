<?php
require 'db.php';
require 'auth.php';
require_login();

if (!in_array($_SESSION['user']['role'], ['admin','staff'])) {
    header("Location: index.php");
    exit;
}

$view_id = (int)($_GET['id'] ?? 0);
if ($view_id <= 0) {
    header("Location: admin_users.php");
    exit;
}

// fetch user
$ust = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$ust->execute([$view_id]);
$user = $ust->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header("Location: admin_users.php");
    exit;
}

$isAdmin = ($_SESSION['user']['role'] === 'admin');

require 'admin_header.php';
?>
<section class="container card" style="padding:20px">
  <h2>User #<?= (int)$user['id'] ?> — <?= htmlspecialchars($user['name']) ?></h2>
  <p>Email: <?= htmlspecialchars($user['email']) ?></p>
  <p>Role: <?= htmlspecialchars($user['role']) ?></p>

  <?php if ($isAdmin): ?>
    <p>Address: <?= htmlspecialchars($user['address']) ?></p>
    <p>Date of Birth: <?= htmlspecialchars($user['date_of_birth']) ?></p>
    <p>Account Created: <?= htmlspecialchars($user['created_at']) ?></p>
  <?php else: ?>
    <p>(Some personal details hidden for staff)</p>
  <?php endif; ?>



  <hr>

  <h3>Bookings</h3>
  <?php
    $bst = $pdo->prepare("
      SELECT b.*, r.number AS roomNumber, r.type AS roomType
      FROM bookings b
      JOIN rooms r ON b.room_id=r.id
      WHERE b.user_id=?
      ORDER BY b.check_in DESC
    ");
    $bst->execute([$view_id]);
    $bookings = $bst->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <?php if ($bookings): ?>
    <table class="table">
      <thead>
        <tr>
          <th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th>
          <th>Guests</th><th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['roomType']).' #'.$b['roomNumber'] ?></td>
            <td><?= htmlspecialchars($b['check_in']) ?></td>
            <td><?= htmlspecialchars($b['check_out']) ?></td>
            <td><?= htmlspecialchars($b['status']) ?></td>
            <td><?= (int)$b['guests'] ?></td>
            <td><a class="btn" href="room.php?id=<?= $b['room_id'] ?>">View Room</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="muted">No bookings found.</p>
  <?php endif; ?>

  <div style="margin-top:14px">
    <a class="btn" href="admin_users.php">← Back to Users</a>
  </div>
</section>
<?php require 'admin_footer.php'; ?>

<?php
require 'db.php';
require 'auth.php';
require_login();

// Only admin allowed
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$room_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Room not found.");
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $rate_dollars = trim($_POST['rate'] ?? '');
    $max_guests = (int)($_POST['max_guests'] ?? 1);
    $inventory = (int)($_POST['inventory'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($type) || empty($number) || !is_numeric($rate_dollars) || $max_guests <= 0 || $inventory < 0) {
        $err = "Please fill all fields correctly. Rate must be a number.";
    } else {
        $rate_cents = (int)($rate_dollars * 100);

        $update = $pdo->prepare(
            "UPDATE rooms SET type=?, number=?, rate_cents=?, max_guests=?, inventory=?, is_active=? WHERE id=?"
        );
        $update->execute([$type, $number, $rate_cents, $max_guests, $inventory, $is_active, $room_id]);
        
        // Refresh data to show in form
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $msg = "Room updated successfully!";
    }
}
?>

<?php require 'header.php'; ?>
<div class="container card" style="padding:20px; max-width:600px; margin:20px auto;">
  <h2>Edit Room</h2>
  <p><strong>Room ID:</strong> #<?= htmlspecialchars($room['id']) ?></p>
  
  <?php if ($msg): ?><div class="flash" style="margin-bottom:10px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="flash error" style="margin-bottom:10px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  
  <form method="post">
    <div style="margin-bottom:10px;">
      <label>Room Type:</label>
      <input type="text" name="type" value="<?= htmlspecialchars($room['type']) ?>" class="input" required>
    </div>
    <div style="margin-bottom:10px;">
      <label>Room Number:</label>
      <input type="text" name="number" value="<?= htmlspecialchars($room['number']) ?>" class="input" required>
    </div>
    <div style="margin-bottom:10px;">
      <label>Rate (in dollars):</label>
      <input type="number" name="rate" value="<?= htmlspecialchars($room['rate_cents'] / 100) ?>" step="0.01" min="0" class="input" required>
    </div>
    <div style="margin-bottom:10px;">
      <label>Max Guests:</label>
      <input type="number" name="max_guests" value="<?= htmlspecialchars($room['max_guests']) ?>" min="1" class="input" required>
    </div>
    <div style="margin-bottom:10px;">
      <label>Inventory:</label>
      <input type="number" name="inventory" value="<?= htmlspecialchars($room['inventory']) ?>" min="0" class="input" required>
    </div>
    <div style="margin-bottom:10px;">
      <label>
        <input type="checkbox" name="is_active" value="1" <?= $room['is_active'] ? 'checked' : '' ?>>
        Active
      </label>
    </div>
    <button type="submit" class="btn primary" style="margin-top:10px;">Save Changes</button>
  </form>
  
  <div style="margin-top:10px;">
    <a class="btn" href="manage_rooms.php">Back to Rooms</a>
  </div>
</div>
<?php require 'footer.php'; ?>

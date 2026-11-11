<?php
require 'db.php';
require 'auth.php';
require_login();

// Only admin can edit roles
if ($_SESSION['user']['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  echo "Invalid user ID.";
  exit;
}

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo "User not found.";
  exit;
}

$err = '';
$roles = ['customer', 'staff', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role  = trim($_POST['role'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $dob = trim($_POST['date_of_birth'] ?? '');

  if ($name === '' || $email === '' || !in_array($role, $roles)) {
    $err = "All fields are required and role must be valid.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = "Please enter a valid email address.";
  } else {
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $check->execute([$email, $id]);
    if ($check->fetch()) {
      $err = "That email is already registered by another user.";
    } else {
      $update = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, address=?, date_of_birth=? WHERE id=?");
      $update->execute([$name, $email, $role, $address, $dob, $id]);

      if ($role !== $user['role']) {
          $handler->destroyUserSessions($id);
      }
      header("Location: admin_users.php");
      exit;
    }
  }
}
?>

<?php require 'admin_header.php'; ?>

<section class="container" style="max-width:600px">
  <div class="card" style="padding:20px">
    <h2>Edit User #<?= $id ?></h2>

    <?php if ($err): ?>
      <div class="flash error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Name:</label>
      <input class="input" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

      <label>Email:</label>
      <input class="input" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label>Role:</label>
      <select class="input" name="role" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Address:</label>
      <input class="input" name="address" value="<?= htmlspecialchars($user['address']) ?>">

      <label>Date of Birth:</label>
      <input class="input" type="date" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth']) ?>">

      <button class="btn primary" style="margin-top:14px">Save Changes</button>
      <a class="btn" href="admin_users.php">Cancel</a>
    </form>
  </div>
</section>

<?php require 'admin_footer.php'; ?>

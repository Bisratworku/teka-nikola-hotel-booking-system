<?php
require 'db.php';
require 'auth.php';

require_role(['admin']);

$err = '';
$ok = '';
$values = [
  'name' => '',
  'email' => '',
  'role' => 'staff',
  'address' => '',
  'date_of_birth' => ''
];

$roles = ['customer', 'staff', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_token();

  $values['name'] = trim($_POST['name'] ?? '');
  $values['email'] = trim($_POST['email'] ?? '');
  $values['role'] = trim($_POST['role'] ?? '');
  $values['address'] = trim($_POST['address'] ?? '');
  $values['date_of_birth'] = trim($_POST['date_of_birth'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($values['name'] === '' || $values['email'] === '' || $password === '' || !in_array($values['role'], $roles, true)) {
    $err = 'Name, email, password and role are required, and role must be valid.';
  } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email address.';
  } elseif (strlen($password) < 8) {
    $err = 'Password must be at least 8 characters.';
  } else {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$values['email']]);

    if ($check->fetch()) {
      $err = 'That email is already registered. Choose another one.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, address, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([
        $values['name'],
        $values['email'],
        $hash,
        $values['role'],
        $values['address'] === '' ? null : $values['address'],
        $values['date_of_birth'] === '' ? null : $values['date_of_birth']
      ]);

      $ok = 'Account created successfully.';
      $values = [
        'name' => '',
        'email' => '',
        'role' => 'staff',
        'address' => '',
        'date_of_birth' => ''
      ];
    }
  }
}
?>

<?php require 'admin_header.php'; ?>

<section class="container" style="max-width:600px">
  <div class="card" style="padding:20px">
    <h2>Add New User</h2>

    <?php if ($err): ?>
      <div class="flash error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="flash"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>

    <form method="post">
      <?php csrf_input(); ?>

      <label>Name:</label>
      <input class="input" name="name" value="<?= htmlspecialchars($values['name']) ?>" required>

      <label>Email:</label>
      <input class="input" type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" required>

      <label>Password:</label>
      <input class="input" type="password" name="password" minlength="8" required>

      <label>Role:</label>
      <select class="input" name="role" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= $values['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Address:</label>
      <input class="input" name="address" value="<?= htmlspecialchars($values['address']) ?>">

      <label>Date of Birth:</label>
      <input class="input" type="date" name="date_of_birth" value="<?= htmlspecialchars($values['date_of_birth']) ?>">

      <div style="margin-top:14px">
        <button class="btn primary" type="submit">Create User</button>
        <a class="btn" href="admin_users.php">Cancel</a>
      </div>
    </form>
  </div>
</section>

<?php require 'admin_footer.php'; ?>

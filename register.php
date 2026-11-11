<?php
require 'db.php';
require 'header.php';

/**
 * Check if a column exists in the users table
 */
function column_exists(PDO $pdo, string $table, string $column): bool {
  static $cache = [];
  $key = "$table.$column";
  if (isset($cache[$key])) return $cache[$key];
  $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
  $stmt->execute([$column]);
  $cache[$key] = (bool) $stmt->fetch();
  return $cache[$key];
}

$err = '';
$ok = '';
$values = [
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'address' => '',
  'date_of_birth' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($values as $k => $_) {
    $values[$k] = trim($_POST[$k] ?? '');
  }
  $password = $_POST['password'] ?? '';

  // Validate fields
  if ($values['first_name'] === '' || $values['last_name'] === '' || $values['email'] === '' || $password === '') {
    $err = 'First name, last name, email and password are required.';
  } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email address.';
  } elseif (strlen($password) < 8) {
    $err = 'Password must be at least 8 characters.';
  } else {
    // Validate DOB if provided
    $dobStore = null;
    if ($values['date_of_birth'] !== '') {
      $dt = DateTime::createFromFormat('Y-m-d', $values['date_of_birth']);
      if (!$dt || $dt->format('Y-m-d') !== $values['date_of_birth']) {
        $err = 'Please enter a valid date of birth.';
      } else {
        $dobStore = $dt->format('Y-m-d');
      }
    }
  }

  if ($err === '') {
    // Check for duplicate email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$values['email']]);
    if ($check->fetch()) {
      $err = 'That email is already registered. Try signing in.';
    } else {
      $name = trim($values['first_name'] . ' ' . $values['last_name']);
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $cols = ['name', 'email', 'password_hash', 'role'];
      $params = [$name, $values['email'], $hash, 'customer'];

      if (column_exists($pdo, 'users', 'address')) {
        $cols[] = 'address';
        $params[] = $values['address'];
      }
      if (column_exists($pdo, 'users', 'date_of_birth')) {
        $cols[] = 'date_of_birth';
        $params[] = $dobStore;
      }

      $sql = 'INSERT INTO users (' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($params)), ',') . ')';
      $ins = $pdo->prepare($sql);
      $ins->execute($params);

      $newId = $pdo->lastInsertId();
      $ok = "✅ Account created successfully! You can now <a href='login.php'>log in</a>.";
      $values = array_map(fn() => '', $values);
    }
  }
}
?>

<section class="container" style="padding-top:28px">
  <div class="card" style="max-width:860px;margin:0 auto;">
    <h2 class="h2" style="margin-bottom:10px">Create an Account</h2>
    <p class="muted" style="margin-top:0">Join The Riverside to book faster and manage your stays.</p>

    <?php if ($err): ?>
      <div class="flash error" style="margin:12px 0"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="flash" style="margin:12px 0"><?= $ok ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on" style="margin-top:10px">
      <div class="grid" style="margin-bottom:10px">
        <div class="span-6">
          <label class="tiny muted">First name</label>
          <input class="input" name="first_name" value="<?= htmlspecialchars($values['first_name']) ?>" required>
        </div>
        <div class="span-6">
          <label class="tiny muted">Last name</label>
          <input class="input" name="last_name" value="<?= htmlspecialchars($values['last_name']) ?>" required>
        </div>
      </div>

      <div class="grid" style="margin-bottom:10px">
        <div class="span-6">
          <label class="tiny muted">Email</label>
          <input class="input" type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" required>
        </div>
        <div class="span-6">
          <label class="tiny muted">Password</label>
          <input class="input" type="password" name="password" minlength="8" autocomplete="off" placeholder="At least 8 characters" required>
        </div>
      </div>

      <div class="grid" style="margin-bottom:10px">

      </div>

      <div style="display:flex;align-items:center;gap:12px;margin-top:8px">
        <button class="btn primary" type="submit">Register</button>
        <span class="muted tiny">Already have an account? <a href="login.php">Sign in</a></span>
      </div>
    </form>
  </div>
</section>

<?php require 'footer.php'; ?>

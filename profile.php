<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/auth.php';
require_login();

$uid = (int)$_SESSION['user']['id'];
$msg = $err = null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (isset($_POST['profile_update'])) {
    $name  = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');

    $up = $pdo->prepare("UPDATE users SET name=?, address=?, date_of_birth=? WHERE id=?");
    $up->execute([$name ?: null, $address ?: null, $dob ?: null, $uid]);

    // refresh session copy
    $_SESSION['user']['name']  = $name;
    $_SESSION['user']['address'] = $address;
    $_SESSION['user']['date_of_birth'] = $dob;

    $msg = "Profile updated.";
  }

  if (isset($_POST['password_change'])) {
    $current = $_POST['current_password'] ?? '';
    $npw     = $_POST['new_password'] ?? '';
    $cpw     = $_POST['confirm_password'] ?? '';

    if (strlen($npw) < 8) {
      $err = "New password must be at least 8 characters.";
    } elseif ($npw !== $cpw) {
      $err = "New password and confirmation do not match.";
    } else {
      $st = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
      $st->execute([$uid]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row || !password_verify($current, $row['password_hash'])) {
        $err = "Current password is incorrect.";
      } else {
        $hash = password_hash($npw, PASSWORD_DEFAULT);
        $up = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $up->execute([$hash, $uid]);
        $msg = "Password changed successfully.";
      }
    }
  }
}

// Load fresh user
$st = $pdo->prepare("SELECT id, email, name, address, date_of_birth, role, created_at FROM users WHERE id=?");
$st->execute([$uid]);
$me = $st->fetch(PDO::FETCH_ASSOC);

require_once __DIR__.'/header.php';

// initial for avatar circle
$initial = strtoupper($me['name'] ? mb_substr($me['name'],0,1) : mb_substr($me['email'],0,1));
?>
<section class="container">
  <div class="grid">
    <div class="span-4">
      <div class="card" style="padding:22px;text-align:center">
        <div style="width:84px;height:84px;border-radius:50%;margin:0 auto 10px;
                    display:flex;align-items:center;justify-content:center;
                    font-weight:800;font-size:28px;background:#e5e7eb;color:#111;">
          <?= htmlspecialchars($initial) ?>
        </div>
        <div class="h3" style="margin:0"><?= htmlspecialchars($me['name'] ?: 'Guest') ?></div>
        <div class="muted" style="font-size:13px;"><?= htmlspecialchars($me['email']) ?></div>
        <div class="chip" style="margin-top:10px;border:1px solid #e5e7eb">
          Role: <?= htmlspecialchars($me['role']) ?>
        </div>
        <?php if (in_array($me['role'],['admin','staff'])): ?>
          <div style="margin-top:12px">
            <a class="btn" href="admin_dashboard.php">Admin Dashboard</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="span-8">
      <?php if ($msg): ?>
        <div class="card" style="padding:12px;color:#0f5132;background:#d1e7dd;border:1px solid #badbcc;margin-bottom:14px">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="card" style="padding:12px;color:#842029;background:#f8d7da;border:1px solid #f5c2c7;margin-bottom:14px">
          <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <div class="card" style="padding:22px;margin-bottom:16px">
        <h2 class="h3" style="margin:0 0 10px">Profile details</h2>
        <form method="post" class="grid">
          <input type="hidden" name="profile_update" value="1">
          <div class="span-12">
            <label>Name
              <input class="input" name="name" value="<?= htmlspecialchars($me['name'] ?? '') ?>" placeholder="Your name">
            </label>
          </div>
          <div class="span-12">
            <label>Address
              <input class="input" name="address" value="<?= htmlspecialchars($me['address'] ?? '') ?>" placeholder="123 Main St, Anytown, USA">
            </label>
          </div>
          <div class="span-6">
            <label>Date of Birth
              <input class="input" type="date" name="date_of_birth" value="<?= htmlspecialchars($me['date_of_birth'] ?? '') ?>">
            </label>
          </div>
          <div class="span-6">
            <label>Email
              <input class="input" value="<?= htmlspecialchars($me['email']) ?>" disabled>
            </label>
          </div>
          <div class="span-12">
            <button type="submit" class="btn primary">Save changes</button>
          </div>
        </form>
      </div>

      <div class="card" style="padding:22px">
        <h2 class="h3" style="margin:0 0 10px">Change password</h2>
        <form method="post" autocomplete="off">
          <input type="hidden" name="password_change" value="1">
          <div style="width:100%; margin-bottom:12px">
            <label>Current password
              <input class="input" type="password" name="current_password" required style="width:100%" autocomplete="current-password">
            </label>
          </div>
          <div style="width:100%; margin-bottom:12px">
            <label>New password
              <input class="input" type="password" name="new_password" minlength="8" required style="width:100%" autocomplete="new-password">
            </label>
          </div>
          <div style="width:100%; margin-bottom:12px">
            <label>Confirm new password
              <input class="input" type="password" name="confirm_password" minlength="8" required style="width:100%" autocomplete="new-password">
            </label>
          </div>
          <div style="width:100%">
            <button type="submit" class="btn primary" formnovalidate>Update password</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</section>
<?php require_once __DIR__.'/footer.php'; ?>
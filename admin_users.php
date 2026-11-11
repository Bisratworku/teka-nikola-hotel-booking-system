<?php

// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Required files
require 'db.php';
require 'auth.php';
require_login();

// Allow only admin or staff to access this page
if (!in_array($_SESSION['user']['role'], ['admin', 'staff'])) {
  header("Location: index.php");
  exit;
}

// Handle search input
$q = trim($_GET['q'] ?? '');
$params = [];
$where = '';

if ($q !== '') {
  $where = "WHERE (name LIKE :q OR email LIKE :q)";
  $params[':q'] = "%$q%";
}

// Get users (basic info only)
$stmt = $pdo->prepare("SELECT id, name, email, role, address, date_of_birth FROM users $where ORDER BY name ASC LIMIT 200");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Admins can see personal info (PII)
$canSeePII = ($_SESSION['user']['role'] === 'admin');
?>

<?php require 'admin_header.php'; ?>

<section class="container">
  <div class="card" style="padding:20px">
    <div class="flex" style="justify-content:space-between;align-items:center">
      <h1 class="h2" style="margin:0">Users</h1>
      <form method="get">
        <input class="input" name="q" placeholder="Search name or email…" value="<?= htmlspecialchars($q) ?>">
      </form>
    </div>

    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
      <div style="margin-top:12px">
        <a class="btn primary" href="admin_user_add.php">Add Receptionist / Admin</a>
      </div>
    <?php endif; ?>

    <div class="table-wrap" style="overflow:auto;margin-top:12px">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
            <?php if ($canSeePII): ?><th>Address</th><th>DOB</th><?php endif; ?>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>#<?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <?php if ($canSeePII): ?>
                <td><?= htmlspecialchars($u['address'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['date_of_birth'] ?? '') ?></td>
              <?php endif; ?>
              <td>
                <a class="btn" href="admin_user_view.php?id=<?= (int)$u['id'] ?>">View</a>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                  <a class="btn" href="admin_user_edit.php?id=<?= (int)$u['id'] ?>">Edit</a>
                  <form method="post" action="admin_user_delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <?php csrf_input(); ?>
                    <button type="submit" class="btn red">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; if (!$users): ?>
            <tr><td colspan="7" class="muted">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require 'admin_footer.php'; ?>

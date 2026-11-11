<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: " . next_after_login());
    exit;
}

$err = '';

// Optional role-specific login request via URL: ?role=admin or ?role=staff
$requested_role = $_GET['role'] ?? '';
if (!in_array($requested_role, ['', 'admin', 'staff'], true)) {
    $requested_role = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // preserve requested role from form (priority over query string)
    $requested_role = $_POST['role'] ?? $requested_role;
    if (!in_array($requested_role, ['', 'admin', 'staff'], true)) {
        $requested_role = '';
    }

    // --- Rate Limiting ---
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_ATTEMPT_WINDOW', 300); // 5 minutes

    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$ip, LOGIN_ATTEMPT_WINDOW]);
    $attempts = (int)$stmt->fetchColumn();

    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $err = 'You have made too many login attempts. Please try again later.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pw    = $_POST['password'] ?? '';

        // Basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Please enter a valid email address.';
        } elseif ($pw === '') {
            $err = 'Password is required.';
        } else {
            // Fetch user by email
            $stmt = $pdo->prepare("SELECT id, name, email, role, password_hash FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($pw, $user['password_hash'])) {
                // enforce role-specific login flow when requested
                if ($requested_role !== '' && $user['role'] !== $requested_role) {
                    $err = 'Invalid role selected for this account. Use the correct login link.';
                } else {
                    // Clear login attempts for this IP
                    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                    $stmt->execute([$ip]);

                    // Store basic user info in session
                    $_SESSION['user'] = [
                        'id'    => $user['id'],
                        'name'  => $user['name'],
                        'email' => $user['email'],
                        'role'  => $user['role'],
                    ];

                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    // Redirect using the centralized, role-aware function
                    header("Location: " . next_after_login());
                    exit;
                }
            } else {
                // Record failed login attempt
                $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, user_agent) VALUES (?, ?)");
                $stmt->execute([$ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);

                // Don't reveal what failed (email or password)
                $err = 'Invalid email or password.';
            }
        }
    }
}

require 'header.php';
?>
<section class="container" style="padding-top:28px">
  <div class="card" style="max-width:560px;margin:0 auto;">
    <h2 class="h2" style="margin:0 0 8px">
      <?php if ($requested_role === 'admin'): ?>Admin sign in<?php elseif ($requested_role === 'staff'): ?>Receptionist sign in<?php else: ?>Sign in<?php endif; ?>
    </h2>
    <p class="muted" style="margin-top:0">
      <?php if ($requested_role === 'admin'): ?>Continue with admin credentials.<?php elseif ($requested_role === 'staff'): ?>Continue with receptionist credentials.<?php else: ?>Welcome back to The Riverside.<?php endif; ?>
    </p>

    <?php if ($err): ?>
      <div class="flash error" style="margin:12px 0">
        <?= htmlspecialchars($err) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="on" style="margin-top:8px">
      <input type="hidden" name="role" value="<?= htmlspecialchars($requested_role) ?>">
      <div style="margin-bottom:12px">
        <label class="tiny muted">Email</label>
        <input class="input" type="email" name="email" required autofocus>
      </div>

      <div style="margin-bottom:6px">
        <label class="tiny muted">Password</label>
        <input class="input" type="password" name="password" id="password" required>
      </div>
      <div style="margin-bottom:6px">
        <input type="checkbox" onclick="showPassword()">
        <label>Show Password</label>
      </div>

      <div style="display:flex;align-items:center;justify-content:flex-end;margin:8px 0 14px">
        <button class="btn primary" type="submit">Login</button>
      </div>
    </form>
    
    <script>
    function showPassword() {
      var x = document.getElementById("password");
      if (x.type === "password") {
        x.type = "text";
      } else {
        x.type = "password";
      }
    }
    </script>

    <div class="muted tiny" style="border-top:1px solid var(--line);padding-top:12px;margin-top:6px">
      No account? <a href="register.php">Create one</a><br>
      Need to change your password? <a href="profile.php">Go to profile</a> after signing in.
    </div>
  </div>
</section>
<?php require 'footer.php'; ?>

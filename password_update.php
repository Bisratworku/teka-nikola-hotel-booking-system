<?php
require 'db.php';
require 'header.php';

$token = trim($_GET['token'] ?? '');
$error_message = '';
$success_message = '';
$is_token_valid = false;

// Step 1: Validate the token from the URL
if ($token) {
    try {
                $stmt = $pdo->prepare(
                    "SELECT pr.id, pr.user_id, pr.expires_at, u.email, u.name
                     FROM password_resets pr
                     JOIN users u ON u.id = pr.user_id
                     WHERE pr.token = ? AND pr.used = 0 LIMIT 1"
                );        $stmt->execute([$token]);
        $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset_request) {
            $expiration_date = new DateTime($reset_request['expires_at']);
            $current_date = new DateTime();

            if ($current_date > $expiration_date) {
                $error_message = 'This password reset link has expired.';
            } else {
                $is_token_valid = true;
            }
        } else {
            $error_message = 'This password reset link is invalid or has already been used.';
        }
    } catch (Exception $e) {
        // This will catch errors from new DateTime() or PDO
        $error_message = 'An unexpected error occurred. Please try again later.';
        // Optionally log the real error: error_log($e->getMessage());
    }} else {
    $error_message = 'No reset token provided.';
}

// Step 2: Handle the form submission
if ($is_token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $submitted_token = $_POST['token'] ?? '';

    if ($token !== $submitted_token) {
        $error_message = 'Token mismatch. Please try again.';
    } elseif (empty($password) || strlen($password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'The two passwords do not match.';
    } else {
        // All checks passed, update the password
        try {
            $pdo->beginTransaction();

            // Hash the new password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Update the user's password
            $update_user_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update_user_stmt->execute([$password_hash, $reset_request['user_id']]);

            // Invalidate the token
            $update_token_stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $update_token_stmt->execute([$reset_request['id']]);

            $pdo->commit();

            $success_message = 'Your password has been successfully updated. You can now log in.';
            $is_token_valid = false; // Hide the form after successful update

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'An error occurred while updating your password. Please try again.';
            // Optionally log the real error: error_log($e->getMessage());
        }
    }
}

?>

<section class="container" style="padding-top:28px">
    <div class="card" style="max-width:560px;margin:0 auto;">
        <h2 class="h2" style="margin:0 0 8px">Reset Your Password</h2>

        <?php if ($error_message): ?>
            <div class="flash error" style="margin:12px 0"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="flash" style="margin:12px 0"><?= htmlspecialchars($success_message) ?></div>
            <p><a href="login.php">Go to Login</a></p>
        <?php endif; ?>

        <?php if ($is_token_valid): ?>
            <p class="muted" style="margin-top:0">Hello <?= htmlspecialchars($reset_request['name'] ?: $reset_request['email']) ?>. Please choose a new password.</p>
            <form method="POST" action="password_update.php?token=<?= htmlspecialchars($token) ?>" style="margin-top:8px">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div style="margin-bottom:10px">
                    <label class="tiny muted" for="password">New Password</label>
                    <input id="password" class="input" type="password" name="password" minlength="8" required>
                </div>

                <div style="margin-bottom:10px">
                    <label class="tiny muted" for="password_confirm">Confirm New Password</label>
                    <input id="password_confirm" class="input" type="password" name="password_confirm" minlength="8" required>
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:12px">
                    <button class="btn primary" type="submit">Update Password</button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</section>

<?php require 'footer.php'; ?>

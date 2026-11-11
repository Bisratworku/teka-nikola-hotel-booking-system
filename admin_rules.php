<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'auth.php';
require 'lang.php';
require 'audit_log.php';

require_login();
require_role(['admin']);

// Load current rule set or default
$rule = $pdo->query("SELECT * FROM reservation_rules ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Set defaults when no rule exists
if (!$rule) {
    $rule = [
        'min_nights' => 1,
        'max_nights' => 30,
        'min_advance_days' => 1,
        'max_advance_days' => 365,
        'max_guests' => null,
    ];
}

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $min_nights = (int)($_POST['min_nights'] ?? 1);
    $max_nights = (int)($_POST['max_nights'] ?? 30);
    $min_advance_days = (int)($_POST['min_advance_days'] ?? 1);
    $max_advance_days = (int)($_POST['max_advance_days'] ?? 365);
    $max_guests = $_POST['max_guests'] === '' ? null : (int)$_POST['max_guests'];

    if ($min_nights < 1) {
        $errors[] = 'Minimum nights must be at least 1.';
    }
    if ($max_nights < $min_nights) {
        $errors[] = 'Maximum nights must be greater than or equal to minimum nights.';
    }
    if ($min_advance_days < 0) {
        $errors[] = 'Minimum advance days cannot be negative.';
    }
    if ($max_advance_days < $min_advance_days) {
        $errors[] = 'Maximum advance days must be greater than or equal to minimum advance days.';
    }
    if ($max_guests !== null && $max_guests < 1) {
        $errors[] = 'Max guests must be at least 1 or empty for unlimited.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO reservation_rules (min_nights, max_nights, min_advance_days, max_advance_days, max_guests) VALUES (?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$min_nights, $max_nights, $min_advance_days, $max_advance_days, $max_guests]);

        if ($ok) {
            audit_log_current_user($pdo, 'reservation_rules_updated', json_encode([ 
                'min_nights' => $min_nights,
                'max_nights' => $max_nights,
                'min_advance_days' => $min_advance_days,
                'max_advance_days' => $max_advance_days,
                'max_guests' => $max_guests,
            ]));
            $success = 'Reservation rules updated successfully.';
            $rule = ['min_nights' => $min_nights, 'max_nights' => $max_nights, 'min_advance_days' => $min_advance_days, 'max_advance_days' => $max_advance_days, 'max_guests' => $max_guests];
        } else {
            $errors[] = 'Could not save reservation rules, please try again.';
        }
    }
}

require 'admin_header.php';
?>

<section class="container">
  <h2><?= htmlspecialchars(t('reservation_rules')) ?></h2>

  <?php if ($success): ?>
    <div class="card" style="background:#ddffdd;border:1px solid #7bcf7b;
        margin-bottom:20px;padding:12px;">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="card" style="background:#ffeaea;border:1px solid #f3a6a6;margin-bottom:20px;padding:12px;">
      <?php foreach ($errors as $e): ?><div>❌ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <?php csrf_input(); ?>

    <div class="form-group"><label><?= htmlspecialchars(t('min_nights')) ?></label>
      <input name="min_nights" type="number" min="1" value="<?= htmlspecialchars($rule['min_nights']) ?>"></div>

    <div class="form-group"><label><?= htmlspecialchars(t('max_nights')) ?></label>
      <input name="max_nights" type="number" min="1" value="<?= htmlspecialchars($rule['max_nights']) ?>"></div>

    <div class="form-group"><label><?= htmlspecialchars(t('min_advance_days')) ?></label>
      <input name="min_advance_days" type="number" min="0" value="<?= htmlspecialchars($rule['min_advance_days']) ?>"></div>

    <div class="form-group"><label><?= htmlspecialchars(t('max_advance_days')) ?></label>
      <input name="max_advance_days" type="number" min="0" value="<?= htmlspecialchars($rule['max_advance_days']) ?>"></div>

    <div class="form-group"><label><?= htmlspecialchars(t('max_guests')) ?></label>
      <input name="max_guests" type="number" min="1" value="<?= htmlspecialchars($rule['max_guests'] ?? '') ?>" placeholder="Leave empty for no limit"></div>

    <button class="btn" type="submit">Save Rules</button>
  </form>
</section>

<?php require 'admin_footer.php'; ?>

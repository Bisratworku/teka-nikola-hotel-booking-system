<?php
// my_bookings.php — latest-first list, extend (no past dates), cancel support
require_once __DIR__.'/db.php';
require_once __DIR__.'/auth.php';
require_login();

$me = $_SESSION['user'];
$uid = (int)$me['id'];
$is_admin = in_array($me['role'] ?? '', ['admin','staff'], true);

$flash = $error = '';



// ---------- POST: Cancel ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cancel_booking_id'])) {
  verify_csrf_token();
  $bid = (int)($_POST['cancel_booking_id'] ?? 0);

  // User can cancel only own booking unless admin/staff
  $q = $pdo->prepare("SELECT b.*, r.inventory
                        FROM bookings b
                        JOIN rooms r ON r.id=b.room_id
                       WHERE b.id=? ".($is_admin ? "" : "AND b.user_id=?"));
  $q->execute($is_admin ? [$bid] : [$bid, $uid]);
  $bk = $q->fetch(PDO::FETCH_ASSOC);

  if (!$bk) {
    $error = 'Booking not found or not permitted.';
  } elseif ($bk['status'] === 'cancelled') {
    $flash = 'That booking is already cancelled.';
  } else {
    $up = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
    $up->execute([$bid]);
    $flash = 'Your booking has been cancelled.';
  }
}

// ---------- Fetch my bookings (numerical order by ID) ----------
$bookings = [];
$sql = "
  SELECT b.*, r.type AS room_type, r.rate_cents
    FROM bookings b
    JOIN rooms r ON r.id=b.room_id
";
if ($is_admin && isset($_GET['all']) && $_GET['all']==='1') {
  // Admin can view all bookings if they add ?all=1
  $sql .= " WHERE 1=1 ";
  $st = $pdo->query($sql." ORDER BY b.id ASC");
} else {
  $sql .= " WHERE b.user_id=? ";
  $st = $pdo->prepare($sql." ORDER BY b.id ASC");
  $st->execute([$uid]);
}
$bookings = $st->fetchAll(PDO::FETCH_ASSOC);

// ---------- Fetch services for each booking ----------
foreach ($bookings as &$b) {
    $q = $pdo->prepare("
        SELECT rs.name, rs.price
        FROM Services_in_Booking sb
        JOIN room_services rs ON sb.service_id = rs.id
        WHERE sb.booking_id = ?
    ");
    $q->execute([$b['id']]);
    $b['services'] = $q->fetchAll(PDO::FETCH_ASSOC);
}
unset($b); // break reference


// helper to format dt
function dt($s) {
  if (!$s) return '';
  // If already datetime, show with time; else show date only
  return (strlen($s) > 10) ? $s : $s.' 00:00:00';
}

require_once __DIR__.'/header.php';
?>
<section class="container">
  <h1 class="h2" style="margin:0 0 12px">My Bookings</h1>

  <?php if ($flash): ?>
    <div class="flash" style="margin:10px 0"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash error" style="margin:10px 0"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$bookings): ?>
    <div class="card"><p class="muted">No bookings yet.</p></div>
  <?php else: ?>
  <div class="card" style="overflow-x:auto">
    <table class="table" style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">#</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Room Type</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Assigned Room #</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Check-in</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Check-out</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Nights</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Status</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Services</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line);width:260px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $today = new DateTime('today');
        foreach ($bookings as $b):
          $ci = new DateTime(substr($b['check_in'], 0, 10));
          $co = new DateTime(substr($b['check_out'], 0, 10));
          $nights = max(1, (int)$ci->diff($co)->days);
          $status = $b['status'];
          $can_extend = ($status==='confirmed' && $status!=='checked_in');
          $can_cancel = ($status!=='cancelled'); // allow cancel anytime; tweak if you want time cutoff
          // min date for extend: max(today, current check-out)
          $minForExtend = max($today->format('Y-m-d'), $co->format('Y-m-d'));
        ?>
        <tr>
          <td style="padding:10px;border-top:1px solid var(--line)">#<?= (int)$b['id'] ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= htmlspecialchars($b['room_type']) ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= $b['unit_number'] ? (int)$b['unit_number'] : '<span class="muted">TBD</span>' ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= htmlspecialchars(dt($b['check_in'])) ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= htmlspecialchars(dt($b['check_out'])) ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= $nights ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= htmlspecialchars($status) ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)">
            <?php if (!empty($b['services'])): ?>
               <?= implode(', ', array_column($b['services'], 'name')) ?>
            <?php else: ?>
              <span class="muted">None</span>
            <?php endif; ?>
          </td>

          <td style="padding:10px;border-top:1px solid var(--line)">
            <?php if ($status !== 'cancelled'): ?>
              <form method="post" style="margin-top:8px">
                <input type="hidden" name="cancel_booking_id" value="<?= (int)$b['id'] ?>">
                <?php csrf_input(); ?>
                <button class="btn" type="submit" onclick="return confirm('Cancel this booking?');">Cancel</button>
              </form>
            <?php else: ?>
              <span class="muted">No actions</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Init Flatpickr on each extend field
document.querySelectorAll('.extend-date').forEach(inp=>{
  const min = inp.dataset.min; // computed server-side: max(today, current checkout)
  flatpickr(inp, {
    dateFormat: "Y-m-d",
    minDate: min,   // no past dates + must be after current checkout
    allowInput: false
  });
});
</script>

<?php require_once __DIR__.'/footer.php'; ?>
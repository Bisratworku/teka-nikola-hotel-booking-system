<?php
// admin_extension_pricing.php - Admin page to set extension pricing
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
require_login();
require_role(['admin', 'staff']);

$flash = $error = '';

// Handle form submission to update pricing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    if (isset($_POST['update_pricing'])) {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $price_cents = (int)($_POST['price_per_night_cents'] ?? 0);
        
        if ($room_id > 0 && $price_cents >= 0) {
            // Check if pricing exists for this room
            $check = $pdo->prepare("SELECT id FROM extension_pricing WHERE room_type_id = ?");
            $check->execute([$room_id]);
            $existing = $check->fetch();
            
            if ($existing) {
                // Update existing pricing
                $update = $pdo->prepare("UPDATE extension_pricing SET price_per_night_cents = ?, updated_at = NOW() WHERE room_type_id = ?");
                $update->execute([$price_cents, $room_id]);
            } else {
                // Insert new pricing
                $insert = $pdo->prepare("INSERT INTO extension_pricing (room_type_id, price_per_night_cents) VALUES (?, ?)");
                $insert->execute([$room_id, $price_cents]);
            }
            
            $flash = 'Extension pricing updated successfully!';
        } else {
            $error = 'Invalid room or price.';
        }
    }
}

// Get all rooms with their current extension pricing
$rooms = $pdo->query("
    SELECT r.id, r.type, r.rate_cents, 
           COALESCE(ep.price_per_night_cents, r.rate_cents) as extension_price_cents,
           ep.is_active as pricing_active
    FROM rooms r
    LEFT JOIN extension_pricing ep ON r.id = ep.room_type_id
    ORDER BY r.type
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__.'/admin_header.php';
?>

<section class="container">
  <h1 class="h2" style="margin:0 0 12px">Extension Pricing Management</h1>
  
  <?php if ($flash): ?>
    <div class="flash" style="margin:10px 0"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash error" style="margin:10px 0"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <p style="margin-bottom:20px">Set custom pricing for booking extensions. If no custom price is set, the regular room rate will be used.</p>
    
    <table class="table" style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Room Type</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Regular Rate/Night</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Extension Rate/Night</th>
          <th style="text-align:left;padding:10px;border-bottom:1px solid var(--line)">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $room): ?>
        <tr>
          <td style="padding:10px;border-top:1px solid var(--line)"><?= htmlspecialchars($room['type']) ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)">$<?= number_format($room['rate_cents'] / 100, 2) ?></td>
          <td style="padding:10px;border-top:1px solid var(--line)">
            $<?= number_format($room['extension_price_cents'] / 100, 2) ?>
            <?php if ($room['extension_price_cents'] == $room['rate_cents']): ?>
              <span class="muted">(default)</span>
            <?php endif; ?>
          </td>
          <td style="padding:10px;border-top:1px solid var(--line)">
            <button class="btn small" onclick="showPriceForm(<?= $room['id'] ?>, '<?= htmlspecialchars($room['type']) ?>', <?= $room['extension_price_cents'] ?>)">Set Price</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Hidden price update form -->
  <div id="priceForm" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.15);z-index:1000;min-width:300px;">
    <h3 style="margin:0 0 15px">Update Extension Price</h3>
    <form method="post">
      <?php csrf_input(); ?>
      <input type="hidden" name="room_id" id="form_room_id">
      <input type="hidden" name="update_pricing" value="1">
      
      <div style="margin-bottom:15px">
        <label style="display:block;margin-bottom:5px"><strong>Room Type:</strong></label>
        <span id="form_room_type"></span>
      </div>
      
      <div style="margin-bottom:15px">
        <label style="display:block;margin-bottom:5px">Extension Price per Night ($):</label>
        <input type="number" 
               name="price_per_night_cents" 
               id="form_price"
               step="0.01" 
               min="0" 
               class="input" 
               required
               style="width:100%">
      </div>
      
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn primary">Update</button>
        <button type="button" class="btn" onclick="hidePriceForm()">Cancel</button>
      </div>
    </form>
  </div>
  
  <div id="priceFormOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;" onclick="hidePriceForm()"></div>
</section>

<script>
function showPriceForm(roomId, roomType, priceCents) {
    document.getElementById('form_room_id').value = roomId;
    document.getElementById('form_room_type').textContent = roomType;
    document.getElementById('form_price').value = (priceCents / 100).toFixed(2);
    document.getElementById('priceForm').style.display = 'block';
    document.getElementById('priceFormOverlay').style.display = 'block';
}

function hidePriceForm() {
    document.getElementById('priceForm').style.display = 'none';
    document.getElementById('priceFormOverlay').style.display = 'none';
}
</script>

<?php require_once __DIR__.'/admin_footer.php'; ?>

<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/audit_log.php';
require_role(['admin']);

// Handle room status update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'update_room_status') {
    verify_csrf_token();
    
    $room_id = (int)$_POST['room_id'];
    $is_active = (int)($_POST['is_active'] ?? 0);
    
    $stmt = $pdo->prepare("UPDATE rooms SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $room_id]);
    
    $status_text = $is_active ? 'activated' : 'deactivated';
    audit_log_current_user($pdo, 'room_status_updated', "Room ID {$room_id} {$status_text}");
    
    header("Location: admin_rooms.php?success=updated");
    exit;
}

// Handle room creation
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    verify_csrf_token();
    
    $number = trim($_POST['number'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $rate = (float)($_POST['rate'] ?? 0);
    $max_guests = (int)($_POST['max_guests'] ?? 2);
    $inventory = (int)($_POST['inventory'] ?? 1);
    $floor = (int)($_POST['floor'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $image_url = null;
    
    // Handle image upload
    if (!empty($_FILES['room_image']['name'])) {
        $f = $_FILES['room_image'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $image_url = 'uploads/room_main_'.time().'.'.$ext;
                move_uploaded_file($f['tmp_name'], $image_url);
            }
        }
    }
    
    if ($number && $type && $rate > 0) {
        $stmt = $pdo->prepare("INSERT INTO rooms (number, type, rate_cents, max_guests, inventory, floor, description, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$number, $type, (int)round($rate*100), $max_guests, $inventory, $floor, $description, $image_url]);
        
        $room_id = $pdo->lastInsertId();
        audit_log_current_user($pdo, 'room_created', "Created new room #{$number} (ID: {$room_id})");
        
        header("Location: admin_rooms.php?success=created");
        exit;
    }
}

// Handle room inventory update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'update_inventory') {
    verify_csrf_token();
    
    $room_id = (int)$_POST['room_id'];
    $new_inventory = (int)$_POST['new_inventory'];
    
    if ($new_inventory >= 0) {
        $stmt = $pdo->prepare("UPDATE rooms SET inventory = ? WHERE id = ?");
        $stmt->execute([$new_inventory, $room_id]);
        
        audit_log_current_user($pdo, 'inventory_updated', "Updated inventory to {$new_inventory} for room ID {$room_id}");
        
        header("Location: admin_rooms.php?success=inventory_updated");
        exit;
    }
}

// Handle room deletion
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'delete_room') {
    verify_csrf_token();
    
    $room_id = (int)$_POST['room_id'];
    $stmt = $pdo->prepare("SELECT number FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($room) {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        audit_log_current_user($pdo, 'room_deleted', "Deleted room #{$room['number']} (ID: {$room_id})");
    }
    
    header("Location: admin_rooms.php?success=deleted");
    exit;
}

// Handle photo upload
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'upload_photo') {
    verify_csrf_token();
    
    $room_id = (int)$_POST['room_id'];
    $photo_type = in_array($_POST['photo_type'] ?? 'main', ['main','bathroom','other']) ? $_POST['photo_type'] : 'main';
    $caption = trim($_POST['caption'] ?? '');
    
    // Upload file
    if (!empty($_FILES['image_file']['name'])) {
        $f = $_FILES['image_file'];
        if ($f['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $dest = 'uploads/room'.$room_id.'_'.time().'.'.$ext;
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    $pdo->prepare("INSERT INTO room_photos (room_id, url, photo_type, caption) VALUES (?,?,?,?)")
                        ->execute([$room_id, $dest, $photo_type, $caption ?: null]);
                    audit_log_current_user($pdo, 'photo_uploaded', "Uploaded photo for room ID {$room_id}");
                }
            }
        }
    }
    // Handle URL
    elseif (!empty($_POST['image_url'])) {
        $url = trim($_POST['image_url']);
        $pdo->prepare("INSERT INTO room_photos (room_id, url, photo_type, caption) VALUES (?,?,?,?)")
            ->execute([$room_id, $url, $photo_type, $caption ?: null]);
        audit_log_current_user($pdo, 'photo_added', "Added photo URL for room ID {$room_id}");
    }
    
    header("Location: admin_rooms.php?room_id={$room_id}#photos");
    exit;
}

// Handle photo deletion
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
    verify_csrf_token();
    
    $photo_id = (int)$_POST['photo_id'];
    $room_id = (int)$_POST['room_id'];
    
    $stmt = $pdo->prepare("SELECT url FROM room_photos WHERE id = ?");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($photo && file_exists($photo['url'])) {
        unlink($photo['url']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM room_photos WHERE id = ?");
    $stmt->execute([$photo_id]);
    audit_log_current_user($pdo, 'photo_deleted', "Deleted photo ID {$photo_id}");
    
    header("Location: admin_rooms.php?room_id={$room_id}#photos");
    exit;
}

// Fetch all rooms
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY type, number")->fetchAll(PDO::FETCH_ASSOC);

// Fetch photos for specific room if requested
$selected_room = null;
$room_photos = [];
if (isset($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $selected_room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_room) {
        $stmt = $pdo->prepare("SELECT * FROM room_photos WHERE room_id = ? ORDER BY id DESC");
        $stmt->execute([$room_id]);
        $room_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

require_once __DIR__.'/admin_header.php';
?>

<section class="container">
  <h2>Room Management</h2>
  
  <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
    <div class="card" style="border:1px solid #0f5132; background:#d1e7dd; color:#0f5132; margin-bottom:12px; padding:12px;">
      ✓ Room status updated successfully!
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
    <div class="card" style="border:1px solid #0f5132; background:#d1e7dd; color:#0f5132; margin-bottom:12px; padding:12px;">
      ✓ Room created successfully!
    </div>
  <?php endif; ?>
  
  <?php if (isset($_GET['success']) && $_GET['success'] === 'inventory_updated'): ?>
    <div class="card" style="border:1px solid #0f5132; background:#d1e7dd; color:#0f5132; margin-bottom:12px; padding:12px;">
      ✓ Room inventory updated successfully!
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
    <div class="card" style="border:1px solid #0f5132; background:#d1e7dd; color:#0f5132; margin-bottom:12px; padding:12px;">
      ✓ Room deleted successfully!
    </div>
  <?php endif; ?>

  <!-- Add New Room Form -->
  <div class="card" style="margin-bottom:20px">
    <h3>Add New Room</h3>
    <form method="post" enctype="multipart/form-data" style="display:grid; gap:16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
      <input type="hidden" name="action" value="create_room">
      <?php csrf_input(); ?>
      
      <div>
        <label for="number">Room Number *</label>
        <input type="text" id="number" name="number" required style="width:100%">
      </div>
      
      <div>
        <label for="type">Room Type *</label>
        <input type="text" id="type" name="type" required placeholder="e.g., Deluxe, Standard, Suite" style="width:100%">
      </div>
      
      <div>
        <label for="rate">Rate per Night ($) *</label>
        <input type="number" id="rate" name="rate" step="0.01" min="0" required style="width:100%">
      </div>
      
      <div>
        <label for="max_guests">Max Guests</label>
        <input type="number" id="max_guests" name="max_guests" value="2" min="1" style="width:100%">
      </div>
      
      <div>
        <label for="inventory">Inventory (Units)</label>
        <input type="number" id="inventory" name="inventory" value="1" min="1" style="width:100%">
      </div>
      
      <div>
        <label for="floor">Floor</label>
        <input type="number" id="floor" name="floor" value="1" min="1" style="width:100%">
      </div>
      
      <div>
        <label for="room_image">Room Image</label>
        <input type="file" id="room_image" name="room_image" accept=".jpg,.jpeg,.png,.webp,.gif" style="width:100%">
      </div>
      
      <div style="grid-column: 1 / -1;">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" style="width:100%"></textarea>
      </div>
      
      <div style="grid-column: 1 / -1;">
        <button type="submit" class="btn primary">Add Room</button>
      </div>
    </form>
  </div>

  <!-- Existing Rooms -->
  <div class="card">
    <h3>Existing Rooms</h3>
    <div style="overflow-x:auto">
      <table class="table" style="width:100%">
        <thead>
          <tr>
            <th>ID</th>
            <th>Room Number</th>
            <th>Type</th>
            <th>Rate/Night</th>
            <th>Max Guests</th>
            <th>Inventory</th>
            <th>Floor</th>
            <th>Status</th>
            <th>Update Inventory</th>
            <th>Edit</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rooms as $room): ?>
            <tr>
              <td><?= (int)$room['id'] ?></td>
              <td><?= htmlspecialchars($room['number']) ?></td>
              <td><?= htmlspecialchars($room['type']) ?></td>
              <td>$<?= number_format($room['rate_cents']/100, 2) ?></td>
              <td><?= (int)$room['max_guests'] ?></td>
              <td><?= (int)$room['inventory'] ?></td>
              <td><?= (int)$room['floor'] ?></td>
              <td>
                <span class="chip" style="background:<?= $room['is_active'] ? '#e6ffe6;border-color:#9fe69f' : '#ffe5e5;border-color:#f0bcbc' ?>">
                  <?= $room['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Update inventory for this room?')">
                  <input type="hidden" name="action" value="update_inventory">
                  <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                  <?php csrf_input(); ?>
                  <div style="display:flex; gap:4px; align-items:center">
                    <input type="number" name="new_inventory" value="<?= (int)$room['inventory'] ?>" min="0" max="999" style="width:60px; padding:2px 4px; font-size:12px" title="Current inventory: <?= (int)$room['inventory'] ?>">
                    <button type="submit" class="btn small" title="Update Inventory">Update</button>
                  </div>
                </form>
              </td>
              <td>
                <form method="post" style="display:inline; margin-right:8px">
                  <input type="hidden" name="action" value="update_room_status">
                  <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                  <input type="hidden" name="is_active" value="<?= $room['is_active'] ? '0' : '1' ?>">
                  <?php csrf_input(); ?>
                  <button type="submit" class="btn small" title="<?= $room['is_active'] ? 'Deactivate Room' : 'Activate Room' ?>">
                    <?= $room['is_active'] ? ' Deactivate' : ' Activate' ?>
                  </button>
                </form>
                <a href="?room_id=<?= (int)$room['id'] ?>#photos" class="btn small" title="Manage Photos">
                   Photos
                </a>
              </td>
              <td style="white-space:nowrap">
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this room permanently?')">
                  <input type="hidden" name="action" value="delete_room">
                  <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                  <?php csrf_input(); ?>
                  <button type="submit" class="btn red small">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Photo Management Section -->
  <?php if ($selected_room): ?>
  <div id="photos" class="card" style="margin-top:20px">
    <h3>Manage Photos — Room <?= htmlspecialchars($selected_room['number']) ?> (<?= htmlspecialchars($selected_room['type']) ?>)</h3>
    
    <!-- Upload Form -->
    <form method="post" enctype="multipart/form-data" style="display:grid; gap:16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom:20px">
      <input type="hidden" name="action" value="upload_photo">
      <input type="hidden" name="room_id" value="<?= (int)$selected_room['id'] ?>">
      <?php csrf_input(); ?>
      
      <div>
        <label for="image_file">Upload Image</label>
        <input type="file" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp,.gif" style="width:100%">
      </div>
      
      <div>
        <label for="image_url">Or Image URL</label>
        <input type="url" id="image_url" name="image_url" placeholder="https://..." style="width:100%">
      </div>
      
      <div>
        <label for="photo_type">Photo Type</label>
        <select id="photo_type" name="photo_type" style="width:100%">
          <option value="main">Main</option>
          <option value="bathroom">Bathroom</option>
          <option value="other">Other</option>
        </select>
      </div>
      
      <div style="grid-column: 1 / -1;">
        <label for="caption">Caption (optional)</label>
        <input type="text" id="caption" name="caption" maxlength="140" placeholder="Short description" style="width:100%">
      </div>
      
      <div style="grid-column: 1 / -1;">
        <button type="submit" class="btn primary">Add Photo</button>
      </div>
    </form>
    
    <!-- Existing Photos -->
    <?php if ($room_photos): ?>
      <h4>Current Photos</h4>
      <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:16px">
        <?php foreach ($room_photos as $photo): ?>
          <div style="border:1px solid #ddd; border-radius:8px; overflow:hidden">
            <?php if (file_exists($photo['url'])): ?>
              <img src="<?= htmlspecialchars($photo['url']) ?>" alt="<?= htmlspecialchars($photo['caption'] ?? 'Room photo') ?>" style="width:100%; height:150px; object-fit:cover">
            <?php else: ?>
              <img src="<?= htmlspecialchars($photo['url']) ?>" alt="<?= htmlspecialchars($photo['caption'] ?? 'Room photo') ?>" style="width:100%; height:150px; object-fit:cover" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5Ij5JbWFnZSBub3QgZm91bmQ8L3RleHQ+PC9zdmc+'">
            <?php endif; ?>
            <div style="padding:8px">
              <div style="font-size:12px; color:#666; margin-bottom:4px">
                Type: <?= htmlspecialchars($photo['photo_type']) ?>
              </div>
              <?php if ($photo['caption']): ?>
                <div style="font-size:12px; margin-bottom:8px"><?= htmlspecialchars($photo['caption']) ?></div>
              <?php endif; ?>
              <form method="post" style="margin:0">
                <input type="hidden" name="action" value="delete_photo">
                <input type="hidden" name="photo_id" value="<?= (int)$photo['id'] ?>">
                <input type="hidden" name="room_id" value="<?= (int)$selected_room['id'] ?>">
                <?php csrf_input(); ?>
                <button type="submit" class="btn red small" onclick="return confirm('Delete this photo?')">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted">No photos uploaded yet for this room.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>

<?php require 'admin_footer.php'; ?>

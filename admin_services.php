<?php
require 'db.php';
require 'auth.php';
require_role(['admin']);
require 'admin_header.php';

$msg = '';
$err = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new service
    if (isset($_POST['add_service'])) {
        $name = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');

        if (empty($name) || !is_numeric($price)) {
            $err = 'Please enter a valid name and price.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO room_services (name, price) VALUES (?, ?)");
            $stmt->execute([$name, $price]);
            $msg = 'Service added successfully.';
        }
    }

    // Update an existing service
    if (isset($_POST['update_service'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');

        if ($id <= 0 || empty($name) || !is_numeric($price)) {
            $err = 'Invalid data provided for updating the service.';
        } else {
            $stmt = $pdo->prepare("UPDATE room_services SET name = ?, price = ? WHERE id = ?");
            $stmt->execute([$name, $price, $id]);
            $msg = 'Service updated successfully.';
        }
    }

    // Delete a service
    if (isset($_POST['delete_service'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM room_services WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Service deleted successfully.';
        }
    }
}

// Fetch all services
$services = $pdo->query("SELECT * FROM room_services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container card" style="padding:20px; max-width:800px; margin:20px auto;">
    <h2>Manage Services</h2>

    <?php if ($msg): ?><div class="flash" style="margin-bottom:10px;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="flash error" style="margin-bottom:10px;"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:20px;">
        <h3>Add New Service</h3>
        <form method="post">
            <input type="hidden" name="add_service" value="1">
            <div style="margin-bottom:10px;">
                <label>Service Name:</label>
                <input type="text" name="name" class="input" required>
            </div>
            <div style="margin-bottom:10px;">
                <label>Price:</label>
                <input type="number" name="price" step="0.01" min="0" class="input" required>
            </div>
            <button type="submit" class="btn primary">Add Service</button>
        </form>
    </div>

    <h3>Existing Services</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Service Name</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= (int)$service['id'] ?>">
                        <td><input type="text" name="name" value="<?= htmlspecialchars($service['name']) ?>" class="input"></td>
                        <td><input type="number" name="price" value="<?= htmlspecialchars($service['price']) ?>" step="0.01" min="0" class="input"></td>
                        <td>
                            <button type="submit" name="update_service" class="btn tiny">Update</button>
                            <button type="submit" name="delete_service" class="btn tiny" onclick="return confirm('Are you sure you want to delete this service?')">Delete</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'admin_footer.php'; ?>

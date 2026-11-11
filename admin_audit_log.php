<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'auth.php';
require 'lang.php';

require_login();
require_role(['admin']);

// Handle printable view
if (isset($_GET['view']) && $_GET['view'] === 'print') {
    // Get search parameters
    $search_user = $_GET['search_user'] ?? '';
    $search_event = $_GET['search_event'] ?? '';
    $search_description = $_GET['search_description'] ?? '';
    
    // Build the query for printable view (no LIMIT for full export)
    $query = "SELECT a.*, u.name as user_name, u.email as user_email FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
    $params = [];
    
    if (!empty($search_user)) {
        $query .= " AND (a.user LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search_user%";
        $params[] = "%$search_user%";
        $params[] = "%$search_user%";
    }
    
    if (!empty($search_event)) {
        $query .= " AND a.event_type LIKE ?";
        $params[] = "%$search_event%";
    }
    
    if (!empty($search_description)) {
        $query .= " AND a.event_details LIKE ?";
        $params[] = "%$search_description%";
    }
    
    $query .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Output printable HTML
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Audit Log Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .filters {
                margin-bottom: 20px;
                padding: 10px;
                background-color: #f5f5f5;
                border-radius: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .no-print {
                display: none;
            }
            @media print {
                .page-break {
                    page-break-after: always;
                }
                body {
                    margin: 10px;
                }
                th, td {
                    font-size: 10px;
                    padding: 5px;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?= htmlspecialchars(HOTEL_NAME) ?> - Audit Log Report</h1>
            <p>Generated on: <?= date('Y-m-d H:i:s') ?></p>
        </div>
        
        <?php if (!empty($search_user) || !empty($search_event) || !empty($search_description)): ?>
        <div class="filters">
            <strong>Filters Applied:</strong><br>
            <?php if (!empty($search_user)): ?>User: <?= htmlspecialchars($search_user) ?><br><?php endif; ?>
            <?php if (!empty($search_event)): ?>Event Type: <?= htmlspecialchars($search_event) ?><br><?php endif; ?>
            <?php if (!empty($search_description)): ?>Description: <?= htmlspecialchars($search_description) ?><br><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Event Type</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td><?= htmlspecialchars($row['user'] ?: $row['user_name'] ?: $row['user_email'] ?: 'System') ?></td>
                    <td><?= htmlspecialchars($row['event_type']) ?></td>
                    <td><?= htmlspecialchars($row['event_details']) ?></td>
                    <td><?= htmlspecialchars($row['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: center; font-size: 10px;">
            <p>Total Records: <?= count($logs) ?></p>
        </div>
        
        <script>
            // Auto-print when page loads
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Get search parameters
$search_user = $_GET['search_user'] ?? '';
$search_event = $_GET['search_event'] ?? '';
$search_description = $_GET['search_description'] ?? '';

// Build the base query
$query = "SELECT a.*, u.name as user_name, u.email as user_email FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
$params = [];

// Add search conditions
if (!empty($search_user)) {
    $query .= " AND (a.user LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search_user%";
    $params[] = "%$search_user%";
    $params[] = "%$search_user%";
}

if (!empty($search_event)) {
    $query .= " AND a.event_type LIKE ?";
    $params[] = "%$search_event%";
}

if (!empty($search_description)) {
    $query .= " AND a.event_details LIKE ?";
    $params[] = "%$search_description%";
}

$query .= " ORDER BY a.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require 'admin_header.php';
?>
<section class="container">
  <h2><?= htmlspecialchars(t('audit_log')) ?></h2>

  <!-- Search Form -->
  <div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
      <div style="flex: 1; min-width: 200px;">
        <label for="search_user" style="display: block; margin-bottom: 5px; font-weight: bold;">User:</label>
        <input type="text" id="search_user" name="search_user" value="<?= htmlspecialchars($search_user) ?>" 
               placeholder="Search by user name or email" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      
      <div style="flex: 1; min-width: 200px;">
        <label for="search_event" style="display: block; margin-bottom: 5px; font-weight: bold;">Event Type:</label>
        <input type="text" id="search_event" name="search_event" value="<?= htmlspecialchars($search_event) ?>" 
               placeholder="Search by event type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      
      <div style="flex: 1; min-width: 200px;">
        <label for="search_description" style="display: block; margin-bottom: 5px; font-weight: bold;">Description:</label>
        <input type="text" id="search_description" name="search_description" value="<?= htmlspecialchars($search_description) ?>" 
               placeholder="Search by description" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      
      <div style="display: flex; gap: 10px;">
        <button type="submit" style="padding: 8px 16px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        <a href="admin_audit_log.php" style="padding: 8px 16px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Clear</a>
        <a href="admin_audit_log.php?view=print&search_user=<?= urlencode($search_user) ?>&search_event=<?= urlencode($search_event) ?>&search_description=<?= urlencode($search_description) ?>" 
           target="_blank" style="padding: 8px 16px; background-color: #17a2b8; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Print</a>
      </div>
    </form>
  </div>

  <?php if (!empty($search_user) || !empty($search_event) || !empty($search_description)): ?>
    <p style="margin-bottom: 15px; color: #666;">
      Found <?= count($logs) ?> result(s) for your search criteria.
    </p>
  <?php endif; ?>

  <div class="card" style="overflow-x:auto">
    <table class="table" style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th>#</th>
          <th><?= htmlspecialchars(t('date')) ?></th>
          <th>User</th>
          <th>Event</th>
          <th><?= htmlspecialchars(t('description')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td><?= htmlspecialchars($row['user'] ?: $row['user_name'] ?: $row['user_email'] ?: 'System') ?></td>
            <td><?= htmlspecialchars($row['event_type']) ?></td>
            <td><?= htmlspecialchars($row['event_details']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require 'admin_footer.php'; ?>

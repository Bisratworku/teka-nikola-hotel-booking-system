<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';
?>

<section class="container">
  <div class="card">
    <h2 class="h2">Test Page</h2>
    <p>This is a simple test page to verify navigation works.</p>
    <p>If you can see this page, then navigation from the "Book Now" button is working.</p>
    
    <div style="margin: 20px 0;">
      <h3>Test Links:</h3>
      <a href="index.php" class="btn">Back to Home</a>
      <a href="rooms_list.php" class="btn primary">Go to Rooms List</a>
    </div>
    
    <div style="margin: 20px 0;">
      <h3>Debug Info:</h3>
      <p>Current URL: <?php echo $_SERVER['REQUEST_URI']; ?></p>
      <p>Database Status: <?php 
        try {
          $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
          echo "✅ Connected to database '" . DB_NAME . "'";
        } catch (PDOException $e) {
          echo "❌ Database connection failed: " . $e->getMessage();
        }
      ?></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>

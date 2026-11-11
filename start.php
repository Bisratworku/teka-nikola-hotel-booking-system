<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection settings
$host = 'localhost';
$user = 'root';           // Replace with your DB user
$pass = '';               // Replace with your DB password
$dbName = 'hotelapp';
    $dumpFile = __DIR__ . '/schema.sql';
try {
    // Step 1: Connect to MySQL server (no DB selected yet)
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Step 2: Drop the database if it exists, then create it.
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "<p>✅ Database '$dbName' dropped if it existed.</p>";
    $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "<p>✅ Database '$dbName' created.</p>";

    // Step 3: Connect to the new database
    $pdo->exec("USE `$dbName`");

    // Step 4: Load and execute the SQL dump file
    if (!file_exists($dumpFile)) {
        throw new Exception("SQL dump file not found at: $dumpFile");
    }

    $sql = file_get_contents($dumpFile);

    // Split SQL script into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    echo "<p>✅ Database tables and schema imported successfully.</p>";



    // Step 5: Ensure a default admin user exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $checkAdmin->execute();
    $adminCount = $checkAdmin->fetchColumn();

    if ($adminCount == 0) {
        $name = 'Admin User';
        $email = 'admin@example.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $role = 'admin';

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);

        echo "<p>✅ Default admin account created: <strong>$email</strong> / password123</p>";
    } else {
        echo "<p>✅ Admin account already exists. No new admin created.</p>";
    }

    echo "<p>🎉 Setup complete. You can now <a href='index.php'>start using the app</a>.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ DB Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}

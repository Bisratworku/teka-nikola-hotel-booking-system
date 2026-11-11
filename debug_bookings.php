<?php
require 'db.php';

// Check all bookings
$all = $pdo->query("SELECT id, status, check_in, check_out, created_at FROM bookings ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>All Bookings (Last 10)</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Created</th></tr>";
foreach ($all as $b) {
    echo "<tr><td>{$b['id']}</td><td>{$b['status']}</td><td>{$b['check_in']}</td><td>{$b['check_out']}</td><td>{$b['created_at']}</td></tr>";
}
echo "</table>";

// Check what the check-in queue query returns
$queue = $pdo->query("SELECT b.id, b.check_in, b.check_out, b.status, u.name AS guest_name, u.email AS guest_email, r.type AS room_type, r.number AS room_number
                      FROM bookings b
                      JOIN users u ON u.id = b.user_id
                      JOIN rooms r ON r.id = b.room_id
                      WHERE b.status IN ('confirmed', 'checked_in')
                      ORDER BY b.check_in ASC, b.created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Check-in Queue Results</h2>";
echo "<p>Count: " . count($queue) . "</p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Status</th><th>Guest</th><th>Room</th><th>Check In</th></tr>";
foreach ($queue as $b) {
    echo "<tr><td>{$b['id']}</td><td>{$b['status']}</td><td>{$b['guest_name']}</td><td>{$b['room_number']}</td><td>{$b['check_in']}</td></tr>";
}
echo "</table>";
?>

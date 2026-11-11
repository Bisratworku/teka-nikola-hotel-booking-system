<?php
require_once __DIR__.'/auth.php';
require_login();

$me = $_SESSION['user'];
$recent_bookings = [];
$upcoming_bookings = [];

// Get recent bookings
$stmt = $pdo->prepare("
    SELECT b.*, r.type AS room_type, r.rate_cents
    FROM bookings b
    JOIN rooms r ON r.id=b.room_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->execute([$me['id']]);
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming bookings
$stmt = $pdo->prepare("
    SELECT b.*, r.type AS room_type, r.rate_cents
    FROM bookings b
    JOIN rooms r ON r.id=b.room_id
    WHERE b.user_id = ? AND b.check_in >= CURDATE() AND b.status = 'confirmed'
    ORDER BY b.check_in ASC
    LIMIT 3
");
$stmt->execute([$me['id']]);
$upcoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__.'/header.php';
?>

<section class="hero-section">
  <div class="hero-background">
    <img src="https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1920" alt="Resort pool with ocean view">
  </div>
  <div class="hero-overlay">
    <div class="container">
      <div class="hero-content">
        <h1 class="hero-title">Welcome back, <?= htmlspecialchars($me['name'] ?? 'Guest') ?>!</h1>
        <p class="hero-subtitle">Manage your bookings and explore our exclusive offers.</p>
        <div class="hero-buttons">
          <a href="rooms_list.php" class="btn primary">Book New Stay</a>
          <a href="my_bookings.php" class="btn">My Bookings</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container">
  <div class="dashboard-grid">
    <!-- Welcome Card -->
    <div class="dashboard-card">
      <h2 class="h2">Your Dashboard</h2>
      <p class="lead">Welcome to your personal space at <?= htmlspecialchars(HOTEL_NAME) ?></p>
      <div class="dashboard-stats">
        <div class="stat-item">
          <div class="stat-number"><?= count($recent_bookings) ?></div>
          <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-item">
          <div class="stat-number"><?= count($upcoming_bookings) ?></div>
          <div class="stat-label">Upcoming Stays</div>
        </div>
      </div>
    </div>

    <!-- Upcoming Bookings -->
    <?php if (!empty($upcoming_bookings)): ?>
    <div class="dashboard-card">
      <h3 class="h3">Your Upcoming Stays</h3>
      <div class="booking-list">
        <?php foreach ($upcoming_bookings as $booking): ?>
          <div class="booking-item">
            <div class="booking-info">
              <div class="booking-room"><?= htmlspecialchars($booking['room_type']) ?></div>
              <div class="booking-dates">
                <?= htmlspecialchars(substr($booking['check_in'], 0, 10)) ?> - 
                <?= htmlspecialchars(substr($booking['check_out'], 0, 10)) ?>
              </div>
            </div>
            <div class="booking-status confirmed"><?= htmlspecialchars($booking['status']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="margin-top: 20px;">
        <a href="my_bookings.php" class="btn primary">View All Bookings</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="dashboard-card">
      <h3 class="h3">Quick Actions</h3>
      <div class="action-grid">
        <a href="rooms_list.php" class="action-item">
          <div class="action-icon">🏨</div>
          <div class="action-text">Book a Room</div>
        </a>
        <a href="my_bookings.php" class="action-item">
          <div class="action-icon">📋</div>
          <div class="action-text">My Bookings</div>
        </a>
        <a href="profile.php" class="action-item">
          <div class="action-icon">👤</div>
          <div class="action-text">Edit Profile</div>
        </a>
        <a href="index.php#amenities" class="action-item">
          <div class="action-icon">🏊</div>
          <div class="action-text">Amenities</div>
        </a>
      </div>
    </div>

    <!-- Special Offers -->
    <div class="dashboard-card">
      <h3 class="h3">Special Offers</h3>
      <div class="offer-item">
        <div class="offer-content">
          <h4>Weekend Getaway</h4>
          <p>Enjoy 20% off on weekend stays. Perfect for a quick escape!</p>
        </div>
        <div class="offer-badge">20% OFF</div>
      </div>
      <div class="offer-item">
        <div class="offer-content">
          <h4>Extended Stay Discount</h4>
          <p>Stay 5 nights or more and receive 15% off your entire booking.</p>
        </div>
        <div class="offer-badge">15% OFF</div>
      </div>
      <div style="margin-top: 20px;">
        <a href="rooms_list.php" class="btn primary">Explore Offers</a>
      </div>
    </div>
  </div>
</section>

<style>
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 30px;
  margin-top: 40px;
}

.dashboard-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow);
  padding: 30px;
}

.dashboard-stats {
  display: flex;
  gap: 40px;
  margin: 30px 0;
}

.stat-item {
  text-align: center;
}

.stat-number {
  font: 700 48px/1 var(--serif);
  color: var(--accent);
  margin-bottom: 5px;
}

.stat-label {
  font-size: 14px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.booking-list {
  margin: 20px 0;
}

.booking-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid var(--line);
}

.booking-item:last-child {
  border-bottom: none;
}

.booking-room {
  font-weight: 600;
  margin-bottom: 5px;
}

.booking-dates {
  font-size: 14px;
  color: var(--muted);
}

.booking-status {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.booking-status.confirmed {
  background: #d4edda;
  color: #155724;
}

.action-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
  margin: 20px 0;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  border: 1px solid var(--line);
  border-radius: 12px;
  text-decoration: none;
  color: var(--ink);
  transition: all 0.3s ease;
}

.action-item:hover {
  background: var(--bg);
  transform: translateY(-2px);
  box-shadow: var(--shadow-soft);
}

.action-icon {
  font-size: 24px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--chip);
  border-radius: 50%;
}

.action-text {
  font-weight: 600;
}

.offer-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border: 1px solid var(--line);
  border-radius: 12px;
  margin-bottom: 15px;
}

.offer-content h4 {
  margin: 0 0 5px 0;
  font-size: 18px;
}

.offer-content p {
  margin: 0;
  font-size: 14px;
  color: var(--muted);
}

.offer-badge {
  background: var(--accent);
  color: var(--accent-ink);
  padding: 8px 12px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 12px;
}

@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .dashboard-stats {
    gap: 20px;
  }
  
  .stat-number {
    font-size: 36px;
  }
  
  .action-grid {
    grid-template-columns: 1fr;
  }
  
  .offer-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>

<?php require_once __DIR__.'/footer.php'; ?>

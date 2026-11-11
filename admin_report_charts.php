<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
require 'auth.php';
require 'lang.php';

require_login();
require_role(['admin']);

$statusData = $pdo->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$roomTypeData = $pdo->query("SELECT r.type, COUNT(*) as cnt FROM bookings b JOIN rooms r ON b.room_id = r.id GROUP BY r.type")->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [];
$statusValues = [];
foreach ($statusData as $item) {
    $statusLabels[] = htmlspecialchars($item['status']);
    $statusValues[] = (int)$item['cnt'];
}

$roomTypeLabels = [];
$roomTypeValues = [];
foreach ($roomTypeData as $item) {
    $roomTypeLabels[] = htmlspecialchars($item['type']);
    $roomTypeValues[] = (int)$item['cnt'];
}

require 'admin_header.php';
?>
<section class="container">
  <h2><?= htmlspecialchars(t('report_charts')) ?></h2>

  <div class="card" style="padding:20px;margin-bottom:20px">
    <h3><?= htmlspecialchars(t('bookings')) ?> by Status</h3>
    <canvas id="statusChart"></canvas>
  </div>

  <div class="card" style="padding:20px;margin-bottom:20px">
    <h3><?= htmlspecialchars(t('rooms')) ?> Booked by Type</h3>
    <canvas id="roomTypeChart"></canvas>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusLabels = <?= json_encode($statusLabels) ?>;
const statusData = <?= json_encode($statusValues) ?>;
const roomTypeLabels = <?= json_encode($roomTypeLabels) ?>;
const roomTypeData = <?= json_encode($roomTypeValues) ?>;

new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
        labels: statusLabels,
        datasets: [{
            label: 'Booking status',
            data: statusData,
            backgroundColor: ['#2563EB', '#10B981', '#EF4444'],
        }]
    }
});

new Chart(document.getElementById('roomTypeChart'), {
    type: 'bar',
    data: {
        labels: roomTypeLabels,
        datasets: [{
            label: 'Booked rooms',
            data: roomTypeData,
            backgroundColor: '#2563EB'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>
<?php require 'admin_footer.php'; ?>

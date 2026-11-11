</main>

<footer class="site-footer" style="background:#1e293b; border-top:2px solid #2563eb; color:#fff;">
  <div class="container footer-grid">
    <div>
      <div class="brand brand--footer">
        <span class="brand-name"><?= htmlspecialchars(HOTEL_NAME) ?> - Staff</span>
      </div>
      <p class="muted" style="color:#94a3b8;">Dire Dawa / Sabiyan . 0904272042</p>
    </div>

    <nav class="footer-links">
      <a href="staff_dashboard.php" style="color:#fff;">Dashboard</a>
      <a href="staff_checkin_queue.php" style="color:#fff;">Check-in Queue</a>
      <a href="index.php" style="color:#60a5fa;">← Back to Site</a>
    </nav>
  </div>

  <div class="container tiny muted" style="color:#94a3b8;">
    © <?= date('Y') ?> <?= htmlspecialchars(HOTEL_NAME) ?> · Staff Panel · All rights reserved
  </div>
</footer>

</body>
</html>

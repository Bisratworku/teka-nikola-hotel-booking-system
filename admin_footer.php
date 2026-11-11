</main>

<footer class="site-footer" style="background:#1f2937; border-top:2px solid #dc2626; color:#fff;">
  <div class="container footer-grid">
    <div>
      <div class="brand brand--footer">
        <span class="brand-name"><?= htmlspecialchars(HOTEL_NAME) ?> - Admin</span>
      </div>
      <p class="muted" style="color:#9ca3af;">Administrator Panel · 353 Main Ave, Norwalk, CT · 0904272042</p>
    </div>

    <nav class="footer-links">
      <a href="admin_dashboard.php" style="color:#fff;">Dashboard</a>
      <a href="admin_bookings.php" style="color:#fff;">Bookings</a>
      <a href="admin_users.php" style="color:#fff;">Users</a>
      <a href="manage_rooms.php" style="color:#fff;">Rooms</a>
      <a href="admin_services.php" style="color:#fff;">Services</a>
      <a href="admin_audit_log.php" style="color:#fff;">Audit Log</a>
      <a href="admin_dashboard.php" style="color:#60a5fa;">← Back to site</a>
    </nav>
  </div>

  <div class="container tiny muted" style="color:#9ca3af;">
    © <?= date('Y') ?> <?= htmlspecialchars(HOTEL_NAME) ?> · Admin Panel · All rights reserved
  </div>
</footer>

</body>
</html>

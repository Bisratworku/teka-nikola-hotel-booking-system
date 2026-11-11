<?php require_once 'auth.php'; require_once 'config.php'; require_once 'lang.php'; require_once 'db.php'; set_language(); ?>
<!doctype html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars(HOTEL_NAME) ?> - Staff Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="site">
<header class="site-header" style="background:#2563eb; border-bottom:2px solid #1e40af;">
  <div class="container header-row">
    <a href="staff_dashboard.php" class="brand">
      <span class="brand-name" style="color:#fff;"><?= htmlspecialchars(HOTEL_NAME) ?></span>
    </a>
    <nav class="main-nav">
      <a href="staff_dashboard.php" style="color:#fff; font-weight:600;">Dashboard</a>
      <a href="staff_checkin_queue.php" style="color:#fff;">Check-in Queue</a>
    </nav>

    <div style="display:flex;align-items:center;gap:12px">
      <form method="get" action="" style="margin:0">
        <label for="lang-select" class="sr-only"><?= htmlspecialchars(t('select_language')) ?></label>
        <select id="lang-select" name="lang" onchange="this.form.submit()" style="padding:6px;border-radius:6px;border:1px solid #d1d5db;">
          <?= language_options_html() ?>
        </select>
      </form>
    </div>
    
    <!-- Right side: staff profile menu -->
    <div class="nav-right" style="display:flex;align-items:center;gap:12px">
      <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] === 'staff' || $_SESSION['user']['role'] === 'admin')): 
        $u = $_SESSION['user'];
        $initial = strtoupper($u['name'] ? mb_substr($u['name'],0,1) : mb_substr($u['email'],0,1));
      ?>
        <div class="profile-wrap" style="position:relative">
          <button id="avatarBtn" aria-haspopup="menu" aria-expanded="false"
            style="width:36px;height:36px;border-radius:50%;border:1px solid #1e40af;
                   background:#2563eb;color:#fff;display:flex;align-items:center;
                   justify-content:center;font-weight:700;cursor:pointer">
            <?= htmlspecialchars($initial) ?>
          </button>

          <div id="avatarMenu" role="menu" aria-label="Staff menu"
               style="position:absolute;right:0;top:46px;background:#fff;border:1px solid #e5e7eb;
                      border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.08);width:220px;
                      padding:8px;display:none;z-index:1000">
            <div style="padding:8px 10px;border-bottom:1px solid #f1f1f1">
              <div style="font-weight:600"><?= htmlspecialchars($u['name'] ?: $u['email']) ?></div>
              <div class="muted" style="font-size:12px;"><?= htmlspecialchars($u['email']) ?></div>
              <div class="chip" style="margin-top:4px;background:#2563eb;color:#fff;"><?= ucfirst($u['role']) ?></div>
            </div>
            
            <a class="menu-item" href="profile.php"><?= htmlspecialchars(t('profile')) ?></a>
            <?php if ($u['role'] === 'admin'): ?>
              <a class="menu-item" href="admin_dashboard.php" style="color:#dc2626;">Admin Panel</a>
            <?php endif; ?>
            <a class="menu-item" href="index.php" style="color:#059669;">← Back to Site</a>
            <a class="menu-item" href="logout.php" style="color:#dc2626;">Logout</a>
          </div>
        </div>

      <?php else: ?>
        <a href="login.php?role=staff" class="btn" style="background:#2563eb;color:#fff;">Login as Staff</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<script>
(function(){
  const btn  = document.getElementById('avatarBtn');
  const menu = document.getElementById('avatarMenu');
  if (!btn || !menu) return;

  function closeMenu(){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); }
  function toggleMenu(e){
    e.stopPropagation();
    const open = menu.style.display === 'block';
    if (open) closeMenu(); else { menu.style.display='block'; btn.setAttribute('aria-expanded','true'); }
  }
  btn.addEventListener('click', toggleMenu);
  document.addEventListener('click', (e)=>{ if (!menu.contains(e.target) && e.target!==btn) closeMenu(); });
  document.addEventListener('keydown', (e)=>{ if (e.key==='Escape') closeMenu(); });
})();
</script>

<main class="site-main">

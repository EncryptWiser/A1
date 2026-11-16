<?php
require_once __DIR__ . '/../config.php';
require_login();

$pageTitle   = $pageTitle ?? 'Dashboard';
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$userName    = current_user();
$userInitial = strtoupper(substr($userName, 0, 1));

$navItems = [
  ['title'=>'Dashboard',      'url'=>'index.php',
   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
  ['title'=>'Products',       'url'=>'products.php',
   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>'],
  ['title'=>'Categories',     'url'=>'categories.php',
   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>'],
  ['title'=>'Suppliers',      'url'=>'suppliers.php',
   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>'],
  ['title'=>'Stock Movement', 'url'=>'stock.php',
   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>'],
  ['title'=>'Reports',        'url'=>'reports.php',
   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?> &mdash; STOCKer</title>
<script>(function(){try{var t=localStorage.getItem('stocker-theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){document.documentElement.setAttribute('data-theme','light');}})();</script>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/app.js"></script>
</head>
<body>

<!-- ===== LIQUID GLASS TOPNAV ===== -->
<header class="topnav" id="topnav">

  <!-- Left: Brand -->
  <a class="brand" href="index.php">
    <span class="brand-icon">S</span>
    <span class="brand-name">
      <b>STOCKer</b>
      <span class="brand-sub">Inventory ERP</span>
    </span>
  </a>

  <!-- Center: Glass pill nav -->
  <nav class="topnav-pill" role="menubar" aria-label="Main navigation">
    <?php foreach ($navItems as $item): ?>
    <a class="nav-link <?= $pageTitle===$item['title']?'active':'' ?>"
       href="<?= e($item['url']) ?>" role="menuitem"
       <?= $pageTitle===$item['title']?'aria-current="page"':'' ?>>
      <?= $item['icon'] ?>
      <?= e($item['title']) ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Right: Controls -->
  <div class="topnav-right">
    <span class="system-status" aria-label="System online">
      <span class="live-dot" aria-hidden="true"></span>Online
    </span>

    <button class="ctrl-btn" id="themeToggle" type="button" aria-label="Toggle theme" aria-pressed="false">
      <span id="themeIcon" aria-hidden="true">&#9790;</span>
      <span id="themeLabel">Dark</span>
    </button>

    <span class="top-avatar" title="<?= e($userName) ?>" aria-hidden="true"><?= e($userInitial) ?></span>

    <a class="ctrl-btn danger" href="logout.php" title="Sign out">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Sign out
    </a>

    <!-- Mobile burger -->
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
</header>

<!-- ===== MOBILE DRAWER ===== -->
<nav class="nav-drawer" id="navDrawer" aria-label="Mobile navigation">
  <a class="brand" href="index.php">
    <span class="brand-icon">S</span>
    <span class="brand-name"><b>STOCKer</b><span class="brand-sub">Inventory ERP</span></span>
  </a>
  <?php foreach ($navItems as $item): ?>
  <a class="nav-link <?= $pageTitle===$item['title']?'active':'' ?>" href="<?= e($item['url']) ?>">
    <?= $item['icon'] ?> <?= e($item['title']) ?>
  </a>
  <?php endforeach; ?>
  <a class="nav-link" href="logout.php" style="margin-top:auto;color:var(--muted)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    Sign out
  </a>
</nav>
<button class="drawer-scrim" id="drawerScrim" tabindex="-1" aria-label="Close menu"></button>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
  <div class="page-heading-block">
    <div class="eyebrow">Inventory Management</div>
    <h1><?= e($pageTitle) ?></h1>
  </div>
</div>

<?php if ($flashMessage): ?>
<div class="flash-wrap">
  <div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
</div>
<?php endif; ?>

<main class="content">

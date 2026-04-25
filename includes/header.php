<?php
$storeName   = getSetting('store_name') ?: 'Fun Frozen Food';
$logoUrl     = getLogoUrl();
$userInitial = strtoupper(substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? $storeName . ' POS') ?></title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<meta name="theme-color" content="#c0392b">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/icons/icon-192x192.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(ROOT_PATH . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- BASE_URL tersedia untuk semua halaman -->
<script>var BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
</head>
<body>
<nav class="topbar">
  <div class="topbar-brand">
    <a href="<?= BASE_URL ?>/pos.php">
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo">
    </a>
    <span class="brand-name"><?= htmlspecialchars($storeName) ?></span>
  </div>
  <div class="topbar-actions">
    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <a href="<?= BASE_URL ?>/admin/index.php" class="btn-topbar-link hide-mobile" title="Dashboard">
      <i class="fa-solid fa-gauge" style="font-size:13px"></i> Dashboard
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/pos.php" class="btn-icon hide-mobile" title="Kasir">
      <i class="fa-solid fa-cash-register"></i>
    </a>
    <div class="topbar-user">
      <div class="avatar"><?= $userInitial ?></div>
      <span class="hide-mobile"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? '') ?></span>
      <small class="hide-mobile" style="font-size:10px;opacity:.6;margin-left:4px">(<?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>)</small>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn-icon" title="Logout">
      <i class="fa-solid fa-right-from-bracket"></i>
    </a>
  </div>
</nav>

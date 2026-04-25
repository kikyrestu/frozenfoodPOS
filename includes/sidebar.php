<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$isAdmin     = ($_SESSION['user_role'] ?? '') === 'admin';
?>
<!-- Desktop Sidebar -->
<?php if (empty($hideDesktopSidebar)): ?>
<aside class="sidebar">
  <div class="sidebar-section">Menu Utama</div>
  <?php if ($isAdmin): ?>
  <a href="<?= BASE_URL ?>/admin/index.php" class="<?= $currentPage === 'index.php' && $currentDir === 'admin' ? 'active' : '' ?>">
    <i class="fa-solid fa-gauge"></i> Dashboard
  </a>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>/pos.php" class="<?= $currentPage === 'pos.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-cash-register"></i> Kasir / POS
  </a>
  <a href="<?= BASE_URL ?>/kasir_produk.php" class="<?= $currentPage === 'kasir_produk.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-box"></i> Kelola Produk
  </a>

  <?php if ($isAdmin): ?>
  <div class="sidebar-section">Admin Panel</div>
  <a href="<?= BASE_URL ?>/admin/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-box-archive"></i> Produk (Admin)
  </a>
  <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-tags"></i> Kategori
  </a>
  <a href="<?= BASE_URL ?>/admin/stock.php" class="<?= $currentPage === 'stock.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-warehouse"></i> Manajemen Stok
  </a>

  <div class="sidebar-section">Penjualan</div>
  <a href="<?= BASE_URL ?>/admin/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-shopping-bag"></i> Pesanan Online
  </a>
  <a href="<?= BASE_URL ?>/admin/transactions.php" class="<?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-receipt"></i> Transaksi
  </a>
  <a href="<?= BASE_URL ?>/admin/reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-chart-bar"></i> Laporan
  </a>

  <div class="sidebar-section">Pengaturan</div>
  <a href="<?= BASE_URL ?>/admin/settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-gear"></i> Pengaturan Toko
  </a>
  <a href="<?= BASE_URL ?>/admin/users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-users"></i> Pengguna
  </a>
  <?php endif; ?>
</aside>
<?php endif; ?>

<!-- Mobile Bottom Navigation -->
<nav class="bottom-nav">
  <?php if ($isAdmin): ?>
  <a href="<?= BASE_URL ?>/admin/index.php" class="bottom-nav-item <?= $currentPage === 'index.php' && $currentDir === 'admin' ? 'active' : '' ?>">
    <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
  </a>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>/pos.php" class="bottom-nav-item <?= $currentPage === 'pos.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-cash-register"></i><span>Kasir</span>
  </a>
  <?php if ($isAdmin): ?>
  <a href="<?= BASE_URL ?>/admin/transactions.php" class="bottom-nav-item <?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-receipt"></i><span>Transaksi</span>
  </a>
  <button class="bottom-nav-item" id="btnBottomMore" onclick="toggleBottomMenu()">
    <i class="fa-solid fa-ellipsis"></i><span>Lainnya</span>
  </button>
  <?php else: ?>
  <a href="<?= BASE_URL ?>/kasir_produk.php" class="bottom-nav-item <?= $currentPage === 'kasir_produk.php' ? 'active' : '' ?>">
    <i class="fa-solid fa-box"></i><span>Produk</span>
  </a>
  <?php endif; ?>
</nav>

<?php if ($isAdmin): ?>
<!-- Mobile "More" Menu Overlay -->
<div class="bottom-menu-overlay" id="bottomMenuOverlay" onclick="toggleBottomMenu()">
  <div class="bottom-menu-sheet" onclick="event.stopPropagation()">
    <div class="bottom-menu-handle"></div>
    <div class="bottom-menu-grid">
      <a href="<?= BASE_URL ?>/kasir_produk.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(52,152,219,.15);color:#3498db"><i class="fa-solid fa-box"></i></div>
        <span>Kelola Produk</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/products.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(230,126,34,.15);color:#e67e22"><i class="fa-solid fa-box-archive"></i></div>
        <span>Produk Admin</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/categories.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(155,89,182,.15);color:#9b59b6"><i class="fa-solid fa-tags"></i></div>
        <span>Kategori</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/stock.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(39,174,96,.15);color:#27ae60"><i class="fa-solid fa-warehouse"></i></div>
        <span>Stok</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/orders.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(241,196,15,.15);color:#f1c40f"><i class="fa-solid fa-shopping-bag"></i></div>
        <span>Pesanan</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/reports.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(192,57,43,.15);color:#e74c3c"><i class="fa-solid fa-chart-bar"></i></div>
        <span>Laporan</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/settings.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(127,140,141,.15);color:#95a5a6"><i class="fa-solid fa-gear"></i></div>
        <span>Pengaturan</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/users.php" class="bottom-menu-item">
        <div class="bottom-menu-icon" style="background:rgba(41,128,185,.15);color:#2980b9"><i class="fa-solid fa-users"></i></div>
        <span>Pengguna</span>
      </a>
    </div>
  </div>
</div>
<script>
function toggleBottomMenu() {
  var overlay = document.getElementById('bottomMenuOverlay');
  if (overlay.classList.contains('show')) {
    overlay.classList.remove('show');
  } else {
    overlay.classList.add('show');
  }
}
</script>
<?php endif; ?>

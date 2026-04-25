<?php
require_once '../config/config.php';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$pageTitle = 'Dashboard - Admin';

// Today stats
$today = date('Y-m-d');
$stmtToday = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as revenue
                            FROM transactions WHERE DATE(created_at) = ? AND (status IS NULL OR status != 'void')");
$stmtToday->execute([$today]);
$todayStats = $stmtToday->fetch();

// This month
$stmtMonth = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as revenue
                            FROM transactions WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) AND (status IS NULL OR status != 'void')");
$stmtMonth->execute();
$monthStats = $stmtMonth->fetch();

// Total products
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE status=1")->fetchColumn();
$lowStock      = $db->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= low_stock_alert AND status=1")->fetchColumn();
$outStock      = $db->query("SELECT COUNT(*) FROM products WHERE stock = 0 AND status=1")->fetchColumn();

// Pending online orders
$pendingOrders = $db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM online_orders WHERE status='pending'")->fetch();

// Top products today
$topProducts = $db->prepare("SELECT ti.product_name, SUM(ti.qty) as total_qty, SUM(ti.subtotal) as total_sales
    FROM transaction_items ti
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.created_at) = ? AND (t.status IS NULL OR t.status != 'void')
    GROUP BY ti.product_name ORDER BY total_qty DESC LIMIT 5");
$topProducts->execute([$today]);
$topProducts = $topProducts->fetchAll();

// Recent transactions
$recentTrx = $db->query("SELECT t.invoice_no, t.customer_name, t.total, t.payment_method, t.status, t.created_at,
                          u.full_name as cashier_name
                          FROM transactions t LEFT JOIN users u ON t.cashier_id=u.id
                          ORDER BY t.created_at DESC LIMIT 8")->fetchAll();

// Last 7 days chart data
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) as rev FROM transactions WHERE DATE(created_at)=? AND (status IS NULL OR status != 'void')");
    $stmt->execute([$d]);
    $chartData[] = ['date' => date('d/m', strtotime($d)), 'revenue' => (float)$stmt->fetchColumn()];
}

include '../includes/header.php';
?>

<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">

  <div class="page-header">
    <div>
      <div class="page-title">Dashboard</div>
      <div class="page-subtitle"><?= date('l, d F Y') ?></div>
    </div>
    <a href="<?= BASE_URL ?>/pos.php" class="btn btn-primary">
      <i class="fa-solid fa-cash-register"></i> Buka Kasir
    </a>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="fa-solid fa-coins"></i></div>
      <div>
        <div class="stat-value"><?= formatRupiah((float)$todayStats['revenue']) ?></div>
        <div class="stat-label">Pendapatan Hari Ini</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><i class="fa-solid fa-receipt"></i></div>
      <div>
        <div class="stat-value"><?= number_format($todayStats['count']) ?></div>
        <div class="stat-label">Transaksi Hari Ini</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fa-solid fa-chart-line"></i></div>
      <div>
        <div class="stat-value"><?= formatRupiah((float)$monthStats['revenue']) ?></div>
        <div class="stat-label">Pendapatan Bulan Ini</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fa-solid fa-box"></i></div>
      <div>
        <div class="stat-value"><?= number_format($totalProducts) ?></div>
        <div class="stat-label">Total Produk Aktif</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div>
        <div class="stat-value"><?= number_format($lowStock) ?></div>
        <div class="stat-label">Stok Menipis</div>
      </div>
    </div>
  </div>

  <?php if ((int)$pendingOrders['cnt'] > 0): ?>
  <div class="alert alert-warning mb-3" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px">
    <i class="fa-solid fa-shopping-bag" style="font-size:18px"></i>
    <span><strong><?= (int)$pendingOrders['cnt'] ?> pesanan online</strong> menunggu approval (<?= formatRupiah((float)$pendingOrders['total']) ?>)
    <a href="<?= BASE_URL ?>/admin/orders.php?status=pending" style="color:#856404;text-decoration:underline;margin-left:6px">Lihat Pesanan</a></span>
  </div>
  <?php endif; ?>

  <?php if ($outStock > 0): ?>
  <div class="alert alert-danger mb-3">
    <i class="fa-solid fa-circle-xmark"></i>
    <span><strong><?= $outStock ?> produk</strong> kehabisan stok! <a href="<?= BASE_URL ?>/admin/stock.php" style="color:inherit;text-decoration:underline">Update stok sekarang</a></span>
  </div>
  <?php endif; ?>

  <?php if ($lowStock > 0): ?>
  <div class="alert alert-warning mb-3">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <span><strong><?= $lowStock ?> produk</strong> stoknya menipis. <a href="<?= BASE_URL ?>/admin/stock.php" style="color:inherit;text-decoration:underline">Cek stok</a></span>
  </div>
  <?php endif; ?>

  <div class="grid-2" style="gap:20px;margin-bottom:20px">

    <!-- Chart: 7 Hari Terakhir -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-chart-bar" style="color:var(--yellow)"></i> Penjualan 7 Hari Terakhir</span>
      </div>
      <canvas id="salesChart" height="200"></canvas>
    </div>

    <!-- Top Produk -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-fire" style="color:var(--red-light)"></i> Produk Terlaris Hari Ini</span>
      </div>
      <?php if (empty($topProducts)): ?>
      <div class="empty-state" style="padding:30px">
        <i class="fa-solid fa-chart-bar"></i>
        <p>Belum ada transaksi hari ini</p>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Produk</th><th class="text-right">Qty</th><th class="text-right">Total</th></tr></thead>
          <tbody>
          <?php foreach ($topProducts as $i => $p): ?>
          <tr>
            <td><span class="badge badge-<?= $i===0?'warning':($i===1?'info':'secondary') ?>"><?= $i+1 ?></span></td>
            <td><?= htmlspecialchars($p['product_name']) ?></td>
            <td class="text-right fw-bold"><?= number_format($p['total_qty']) ?></td>
            <td class="text-right text-yellow fw-bolder"><?= formatRupiah((float)$p['total_sales']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Transactions -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--info)"></i> Transaksi Terbaru</span>
      <a href="<?= BASE_URL ?>/admin/transactions.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
    </div>
    <?php if (empty($recentTrx)): ?>
    <div class="empty-state" style="padding:30px"><i class="fa-solid fa-receipt"></i><p>Belum ada transaksi</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Invoice</th><th>Customer</th><th>Kasir</th><th>Bayar</th><th class="text-right">Total</th><th>Waktu</th></tr></thead>
        <tbody>
        <?php foreach ($recentTrx as $trx): ?>
        <?php $isVoid = ($trx['status'] ?? '') === 'void'; ?>
        <tr style="<?= $isVoid ? 'opacity:.5' : '' ?>">
          <td><span class="fw-bold text-yellow"><?= htmlspecialchars($trx['invoice_no']) ?></span></td>
          <td><?= htmlspecialchars($trx['customer_name'] ?: '-') ?></td>
          <td><?= htmlspecialchars($trx['cashier_name'] ?: '-') ?></td>
          <td><span class="badge <?= $trx['payment_method']==='tunai'?'badge-success':'badge-info' ?>"><?= ucfirst($trx['payment_method']) ?></span></td>
          <td class="text-right fw-bolder"><?= formatRupiah((float)$trx['total']) ?><?= $isVoid ? ' <span class="badge badge-danger" style="font-size:9px">Void</span>' : '' ?></td>
          <td class="text-muted" style="font-size:12px"><?= date('d/m H:i', strtotime($trx['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('salesChart');
if (ctx) {
  const chartData = <?= json_encode($chartData) ?>;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.map(d => d.date),
      datasets: [{
        label: 'Pendapatan',
        data: chartData.map(d => d.revenue),
        backgroundColor: 'rgba(192,57,43,0.7)',
        borderColor: '#c0392b',
        borderWidth: 2,
        borderRadius: 8,
        hoverBackgroundColor: '#e74c3c'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { display: false }, tooltip: {
        callbacks: { label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID') }
      }},
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888' } },
        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888',
          callback: v => 'Rp ' + (v >= 1000000 ? (v/1000000).toFixed(1)+'jt' : v >= 1000 ? (v/1000).toFixed(0)+'rb' : v)
        }}
      }
    }
  });
}
</script>

<?php include '../includes/footer.php'; ?>

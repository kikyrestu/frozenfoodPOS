<?php
require_once '../config/config.php';
$isAdminPage = true;
requireAdmin();

$db = getDB();

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

// Summary
$summary = $db->prepare("SELECT COUNT(*) as total_trx, COALESCE(SUM(total),0) as total_rev,
    COALESCE(AVG(total),0) as avg_rev,
    COALESCE(SUM(CASE WHEN payment_method='tunai' THEN 1 ELSE 0 END),0) as tunai_count,
    COALESCE(SUM(CASE WHEN payment_method='transfer' THEN 1 ELSE 0 END),0) as transfer_count
    FROM transactions WHERE (status IS NULL OR status != 'void') AND DATE(created_at) BETWEEN ? AND ?");
$summary->execute([$dateFrom, $dateTo]);
$summary = $summary->fetch();

// Daily sales chart
$dailySales = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue
    FROM transactions WHERE (status IS NULL OR status != 'void') AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) ORDER BY date ASC");
$dailySales->execute([$dateFrom, $dateTo]);
$dailySales = $dailySales->fetchAll();

// Top products
$topProducts = $db->prepare("SELECT ti.product_name, SUM(ti.qty) as total_qty, SUM(ti.subtotal) as total_sales,
    COUNT(DISTINCT ti.transaction_id) as tx_count
    FROM transaction_items ti JOIN transactions t ON ti.transaction_id=t.id
    WHERE (t.status IS NULL OR t.status != 'void') AND DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY ti.product_name ORDER BY total_qty DESC LIMIT 10");
$topProducts->execute([$dateFrom, $dateTo]);
$topProducts = $topProducts->fetchAll();

// Stock report
$stockReport = $db->query("SELECT p.name, p.stock, p.unit, p.low_stock_alert, c.name as cat_name,
    CASE WHEN p.stock=0 THEN 'habis' WHEN p.stock<=p.low_stock_alert THEN 'menipis' ELSE 'ok' END as status
    FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY p.stock ASC")->fetchAll();

$pageTitle = 'Laporan - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div><div class="page-title">Laporan Penjualan</div></div>
    <button class="btn btn-secondary no-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak</button>
  </div>

  <!-- Date Filter -->
  <div class="card mb-3 no-print" style="padding:14px">
    <form method="GET" class="d-flex gap-2 align-center flex-wrap">
      <label class="form-label mb-0">Periode:</label>
      <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>" style="width:160px">
      <span class="text-muted">s/d</span>
      <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>" style="width:160px">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Tampilkan</button>
      <button type="button" class="btn btn-secondary" onclick="setToday()">Hari Ini</button>
      <button type="button" class="btn btn-secondary" onclick="setThisMonth()">Bulan Ini</button>
    </form>
  </div>

  <!-- Summary Stats -->
  <div class="stats-grid mb-4">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="fa-solid fa-coins"></i></div>
      <div><div class="stat-value" style="font-size:16px"><?= formatRupiah((float)$summary['total_rev']) ?></div><div class="stat-label">Total Pendapatan</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><i class="fa-solid fa-receipt"></i></div>
      <div><div class="stat-value"><?= number_format($summary['total_trx']) ?></div><div class="stat-label">Total Transaksi</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fa-solid fa-calculator"></i></div>
      <div><div class="stat-value" style="font-size:15px"><?= formatRupiah((float)$summary['avg_rev']) ?></div><div class="stat-label">Rata-rata / Transaksi</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fa-solid fa-money-bill-wave"></i></div>
      <div><div class="stat-value"><?= number_format($summary['tunai_count']) ?></div><div class="stat-label">Bayar Tunai</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fa-solid fa-mobile-screen"></i></div>
      <div><div class="stat-value"><?= number_format($summary['transfer_count']) ?></div><div class="stat-label">Bayar Transfer</div></div>
    </div>
  </div>

  <!-- Daily Chart -->
  <?php if (!empty($dailySales)): ?>
  <div class="card mb-4">
    <div class="card-header"><span class="card-title"><i class="fa-solid fa-chart-line" style="color:var(--yellow)"></i> Grafik Penjualan Harian</span></div>
    <div style="padding:16px">
      <canvas id="dailyChart" height="120"></canvas>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid-2" style="gap:20px;margin-bottom:20px">
    <!-- Top Products -->
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-trophy" style="color:var(--yellow)"></i> Produk Terlaris</span></div>
      <?php if (empty($topProducts)): ?>
      <div class="empty-state" style="padding:30px"><i class="fa-solid fa-box-open"></i><p>Tidak ada data</p></div>
      <?php else: ?>
      <div class="desktop-table">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Produk</th><th class="text-right">Qty</th><th class="text-right">Total</th></tr></thead>
          <tbody>
          <?php foreach ($topProducts as $i => $p): ?>
          <tr>
            <td><span class="badge <?= $i===0?'badge-warning':($i===1?'badge-info':'badge-secondary') ?>"><?= $i+1 ?></span></td>
            <td style="font-size:12px;font-weight:700"><?= htmlspecialchars($p['product_name']) ?></td>
            <td class="text-right fw-bold"><?= number_format($p['total_qty']) ?></td>
            <td class="text-right fw-bolder text-yellow"><?= formatRupiah((float)$p['total_sales']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      </div>
      <div class="mobile-cards" style="padding:12px">
        <?php foreach ($topProducts as $i => $p): ?>
        <div class="m-card">
          <div class="m-card-icon" style="width:32px;height:32px;border-radius:8px;font-size:13px;font-weight:900;background:<?= $i===0?'rgba(241,196,15,.2)':($i===1?'rgba(52,152,219,.15)':'var(--dark)') ?>;color:<?= $i===0?'var(--yellow)':($i===1?'var(--info)':'var(--text-muted)') ?>">
            <?= $i+1 ?>
          </div>
          <div class="m-card-body">
            <div class="m-card-title"><?= htmlspecialchars($p['product_name']) ?></div>
            <div class="m-card-sub"><span><?= number_format($p['total_qty']) ?> terjual</span></div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value text-yellow" style="font-size:12px"><?= formatRupiah((float)$p['total_sales']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Stock Report -->
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-boxes-stacked" style="color:var(--info)"></i> Laporan Stok</span></div>
      <div class="desktop-table">
      <div class="table-wrap" style="max-height:320px;overflow-y:auto">
        <table>
          <thead><tr><th>Produk</th><th class="text-right">Stok</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($stockReport as $s):
            $sc = $s['status']==='habis'?'danger':($s['status']==='menipis'?'warning':'success');
          ?>
          <tr>
            <td style="font-size:12px;font-weight:700"><?= htmlspecialchars(substr($s['name'],0,28)) ?></td>
            <td class="text-right fw-bold text-<?= $sc ?>"><?= $s['stock'] ?> <?= $s['unit'] ?></td>
            <td><span class="badge badge-<?= $sc ?>"><?= $s['status'] ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      </div>
      <div class="mobile-cards" style="padding:12px;max-height:320px;overflow-y:auto">
        <?php foreach ($stockReport as $s):
          $sc = $s['status']==='habis'?'danger':($s['status']==='menipis'?'warning':'success');
        ?>
        <div class="m-card">
          <div class="m-card-body">
            <div class="m-card-title"><?= htmlspecialchars(substr($s['name'],0,28)) ?></div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value text-<?= $sc ?>" style="font-size:13px"><?= $s['stock'] ?> <?= $s['unit'] ?></div>
            <span class="badge badge-<?= $sc ?>" style="font-size:9px;padding:1px 5px"><?= $s['status'] ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Daily Sales Table -->
  <?php if (!empty($dailySales)): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">Detail Penjualan Harian</span></div>
    <div class="desktop-table">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th class="text-right">Jumlah Transaksi</th><th class="text-right">Total Pendapatan</th></tr></thead>
        <tbody>
        <?php foreach ($dailySales as $d): ?>
        <tr>
          <td class="fw-bold"><?= date('d F Y', strtotime($d['date'])) ?></td>
          <td class="text-right"><?= number_format($d['count']) ?> transaksi</td>
          <td class="text-right fw-bolder text-yellow"><?= formatRupiah((float)$d['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    </div>
    <div class="mobile-cards" style="padding:12px">
      <?php foreach ($dailySales as $d): ?>
      <div class="m-card">
        <div class="m-card-body">
          <div class="m-card-title"><?= date('d M Y', strtotime($d['date'])) ?></div>
          <div class="m-card-sub"><span><?= number_format($d['count']) ?> transaksi</span></div>
        </div>
        <div class="m-card-right">
          <div class="m-card-value text-yellow" style="font-size:12px"><?= formatRupiah((float)$d['revenue']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const dailyData = <?= json_encode(array_values($dailySales)) ?>;
const ctx = document.getElementById('dailyChart');

if (ctx && dailyData.length) {
  const labels = dailyData.map(d => d.date);
  const values = dailyData.map(d => parseFloat(d.revenue) || 0);

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          // Bar di belakang (merah transparan)
          type: 'bar',
          label: 'Pendapatan',
          data: values,
          backgroundColor: 'rgba(192,57,43,0.5)',
          borderColor: '#c0392b',
          borderWidth: 2,
          borderRadius: 6,
          barPercentage: 0.5,
          categoryPercentage: 0.5
        },
        {
          // Line di depan
          type: 'line',
          label: 'Trend',
          data: values,
          borderColor: '#c0392b',
          backgroundColor: 'transparent',
          tension: 0.4,
          fill: false,
          pointRadius: 7,
          pointHoverRadius: 9,
          pointBackgroundColor: '#f1c40f',
          pointBorderColor: '#c0392b',
          pointBorderWidth: 2,
          borderWidth: 2
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              if (ctx.datasetIndex !== 0) return null;
              return 'Rp ' + parseFloat(ctx.raw).toLocaleString('id-ID');
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#888' }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: {
            color: '#888',
            callback: function(v) {
              return 'Rp ' + (v >= 1e6 ? (v/1e6).toFixed(1)+'jt' : v >= 1e3 ? (v/1e3).toFixed(0)+'rb' : v);
            }
          }
        }
      }
    }
  });
}

function setToday() {
  const t = new Date().toISOString().slice(0,10);
  document.querySelector('[name=date_from]').value = t;
  document.querySelector('[name=date_to]').value = t;
}

function setThisMonth() {
  const d = new Date();
  document.querySelector('[name=date_from]').value = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01`;
  document.querySelector('[name=date_to]').value = d.toISOString().slice(0,10);
}
</script>

<?php include '../includes/footer.php'; ?>
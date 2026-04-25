<?php
require_once '../config/config.php';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $msg = 'Sesi tidak valid, coba lagi.'; $msgType = 'danger'; }
    else {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_stock') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $type      = in_array($_POST['type'] ?? '', ['in', 'adjustment']) ? $_POST['type'] : 'in';
        $qty       = (int)($_POST['qty'] ?? 0);
        $note      = sanitize($_POST['note'] ?? '');

        if ($productId <= 0 || ($type === 'in' && $qty <= 0) || ($type === 'adjustment' && $qty < 0)) {
            $msg = 'Produk dan jumlah wajib diisi!'; $msgType = 'danger';
        } else {
            $stmt = $db->prepare("SELECT stock, name FROM products WHERE id=?");
            $stmt->execute([$productId]);
            $p = $stmt->fetch();
            if (!$p) {
                $msg = 'Produk tidak ditemukan!'; $msgType = 'danger';
            } else {
                $stockBefore = (int)$p['stock'];
                $stockAfter  = ($type === 'in') ? ($stockBefore + $qty) : $qty;

                $db->prepare("UPDATE products SET stock=?, updated_at=NOW() WHERE id=?")->execute([$stockAfter, $productId]);
                $db->prepare("INSERT INTO stock_history (product_id,type,qty,stock_before,stock_after,note,user_id) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$productId, $type, $qty, $stockBefore, $stockAfter, $note, $_SESSION['user_id']]);

                $label = $type === 'in' ? 'ditambah' : 'disesuaikan menjadi';
                $msg   = "Stok {$p['name']} berhasil {$label} {$qty}. Stok sekarang: {$stockAfter}";
            }
        }
    }
    } // end csrf check
}

$search = sanitize($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? '';
$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'p.name LIKE ?'; $params[] = "%{$search}%"; }
if ($filter === 'low') { $where[] = 'p.stock > 0 AND p.stock <= p.low_stock_alert'; }
if ($filter === 'out') { $where[] = 'p.stock = 0'; }

$products = $db->prepare("SELECT p.*, c.name as cat_name FROM products p
    LEFT JOIN categories c ON p.category_id=c.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.stock ASC, p.name ASC");
$products->execute($params);
$products = $products->fetchAll();

$history = $db->query("SELECT sh.*, p.name as product_name, u.full_name as user_name
    FROM stock_history sh
    JOIN products p ON sh.product_id=p.id
    LEFT JOIN users u ON sh.user_id=u.id
    ORDER BY sh.created_at DESC LIMIT 20")->fetchAll();

$allProducts = $db->query("SELECT id, name, stock, unit FROM products ORDER BY name")->fetchAll();

// Stats
$totalProducts = count($allProducts);
$lowStockCount = $db->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= low_stock_alert")->fetchColumn();
$outStockCount = $db->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();

$pageTitle = 'Stok - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div>
      <div class="page-title">Manajemen Stok</div>
      <div class="page-subtitle"><?= $totalProducts ?> produk total</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('stockModal')">
      <i class="fa-solid fa-plus"></i> Update Stok
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> mb-3">
    <i class="fa-solid fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Stats Cards -->
  <div class="stats-grid mb-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(39,174,96,.15);color:#27ae60"><i class="fa-solid fa-box"></i></div>
      <div class="stat-info">
        <div class="stat-value"><?= $totalProducts ?></div>
        <div class="stat-label">Total Produk</div>
      </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="window.location.href='?filter=low'">
      <div class="stat-icon" style="background:rgba(241,196,15,.15);color:#f1c40f"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div class="stat-info">
        <div class="stat-value text-warning"><?= $lowStockCount ?></div>
        <div class="stat-label">Stok Menipis</div>
      </div>
    </div>
    <div class="stat-card" style="cursor:pointer" onclick="window.location.href='?filter=out'">
      <div class="stat-icon" style="background:rgba(231,76,60,.15);color:#e74c3c"><i class="fa-solid fa-ban"></i></div>
      <div class="stat-info">
        <div class="stat-value text-danger"><?= $outStockCount ?></div>
        <div class="stat-label">Stok Habis</div>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <div class="card mb-3" style="padding:14px">
    <form method="GET" class="d-flex gap-2 flex-wrap align-center">
      <div class="search-wrap" style="flex:1;min-width:180px">
        <i class="fa-solid fa-search"></i>
        <input type="text" name="search" class="form-control search-input" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="filter" class="form-select" style="width:180px">
        <option value="">Semua Stok</option>
        <option value="low" <?= $filter==='low'?'selected':'' ?>>⚠️ Stok Menipis</option>
        <option value="out" <?= $filter==='out'?'selected':'' ?>>❌ Habis</option>
      </select>
      <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="stock.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i></a>
    </form>
  </div>

  <div class="grid-2" style="gap:20px">
    <!-- Stock List -->
    <div class="card">
      <div class="card-header"><span class="card-title">Daftar Stok Produk</span></div>
      <div class="desktop-table">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Produk</th><th>Kategori</th><th class="text-right">Stok</th><th>Satuan</th><th>Status</th></tr>
          </thead>
          <tbody>
          <?php if (empty($products)): ?>
          <tr><td colspan="5"><div class="empty-state" style="padding:20px"><i class="fa-solid fa-box-open"></i><p>Tidak ada produk</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($products as $p):
            $sc = $p['stock'] == 0 ? 'danger' : ($p['stock'] <= $p['low_stock_alert'] ? 'warning' : 'success');
            $sl = $p['stock'] == 0 ? 'Habis' : ($p['stock'] <= $p['low_stock_alert'] ? 'Menipis' : 'OK');
          ?>
          <tr>
            <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['cat_name'] ?? '-') ?></td>
            <td class="text-right fw-bolder text-<?= $sc ?>"><?= number_format($p['stock']) ?></td>
            <td><?= htmlspecialchars($p['unit']) ?></td>
            <td><span class="badge badge-<?= $sc ?>"><?= $sl ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      </div>
      <!-- Mobile Cards -->
      <div class="mobile-cards" style="padding:12px">
        <?php if (empty($products)): ?>
        <div class="empty-state" style="padding:20px"><i class="fa-solid fa-box-open"></i><p>Tidak ada produk</p></div>
        <?php else: ?>
        <?php foreach ($products as $p):
          $sc = $p['stock'] == 0 ? 'danger' : ($p['stock'] <= $p['low_stock_alert'] ? 'warning' : 'success');
          $sl = $p['stock'] == 0 ? 'Habis' : ($p['stock'] <= $p['low_stock_alert'] ? 'Menipis' : 'OK');
        ?>
        <div class="m-card">
          <div class="m-card-body">
            <div class="m-card-title"><?= htmlspecialchars($p['name']) ?></div>
            <div class="m-card-sub">
              <span><?= htmlspecialchars($p['cat_name'] ?? '-') ?></span>
              <span>·</span>
              <span><?= htmlspecialchars($p['unit']) ?></span>
            </div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value text-<?= $sc ?>"><?= number_format($p['stock']) ?></div>
            <span class="badge badge-<?= $sc ?>" style="font-size:9px;padding:2px 6px"><?= $sl ?></span>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stock History -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--info)"></i> Riwayat Perubahan (20 Terakhir)</span>
      </div>
      <div class="desktop-table">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Produk</th><th>Tipe</th><th class="text-right">Sebelum</th><th class="text-right">Sesudah</th><th>Oleh</th><th>Waktu</th></tr>
          </thead>
          <tbody>
          <?php if (empty($history)): ?>
          <tr><td colspan="6"><div class="empty-state" style="padding:20px"><i class="fa-solid fa-history"></i><p>Belum ada riwayat</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($history as $h): ?>
          <tr>
            <td style="font-size:12px;font-weight:700"><?= htmlspecialchars(mb_substr($h['product_name'],0,20)) ?></td>
            <td>
              <span class="badge <?= $h['type']==='in'?'badge-success':($h['type']==='out'?'badge-danger':'badge-warning') ?>">
                <?= $h['type']==='in' ? '➕ Masuk' : ($h['type']==='out' ? '➖ Keluar' : '🔄 Sesuai') ?>
              </span>
            </td>
            <td class="text-right"><?= $h['stock_before'] ?></td>
            <td class="text-right fw-bold <?= $h['stock_after'] > $h['stock_before'] ? 'text-success' : ($h['stock_after'] < $h['stock_before'] ? 'text-danger' : '') ?>"><?= $h['stock_after'] ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($h['user_name'] ?? '-') ?></td>
            <td style="font-size:11px" class="text-muted"><?= date('d/m H:i', strtotime($h['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      </div>
      <!-- Mobile Cards -->
      <div class="mobile-cards" style="padding:12px">
        <?php if (empty($history)): ?>
        <div class="empty-state" style="padding:20px"><i class="fa-solid fa-history"></i><p>Belum ada riwayat</p></div>
        <?php else: ?>
        <?php foreach ($history as $h): ?>
        <div class="m-card">
          <div class="m-card-icon">
            <?php if ($h['type']==='in'): ?><i class="fa-solid fa-arrow-down" style="color:var(--success)"></i>
            <?php elseif ($h['type']==='out'): ?><i class="fa-solid fa-arrow-up" style="color:var(--danger)"></i>
            <?php else: ?><i class="fa-solid fa-arrows-rotate" style="color:var(--warning)"></i>
            <?php endif; ?>
          </div>
          <div class="m-card-body">
            <div class="m-card-title"><?= htmlspecialchars(mb_substr($h['product_name'],0,25)) ?></div>
            <div class="m-card-sub">
              <span><?= htmlspecialchars($h['user_name'] ?? '-') ?></span>
              <span>·</span>
              <span><?= date('d/m H:i', strtotime($h['created_at'])) ?></span>
            </div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value <?= $h['stock_after'] > $h['stock_before'] ? 'text-success' : ($h['stock_after'] < $h['stock_before'] ? 'text-danger' : '') ?>"><?= $h['stock_before'] ?> → <?= $h['stock_after'] ?></div>
            <span class="badge <?= $h['type']==='in'?'badge-success':($h['type']==='out'?'badge-danger':'badge-warning') ?>" style="font-size:9px;padding:2px 6px">
              <?= $h['type']==='in' ? 'Masuk' : ($h['type']==='out' ? 'Keluar' : 'Sesuai') ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Modal Update Stok -->
<div class="modal-overlay" id="stockModal" style="display:none">
  <div class="modal-box" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title">
        <i class="fa-solid fa-warehouse" style="color:var(--yellow)"></i> Update Stok
      </span>
      <button class="modal-close" onclick="closeModal('stockModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form method="POST" id="stockForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_stock">
        <div class="form-group">
          <label class="form-label">Produk *</label>
          <select name="product_id" id="stockProductId" class="form-select" required onchange="updateCurrentStock()">
            <option value="">-- Pilih Produk --</option>
            <?php foreach ($allProducts as $p): ?>
            <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" data-unit="<?= htmlspecialchars($p['unit']) ?>">
              <?= htmlspecialchars($p['name']) ?> (Stok: <?= $p['stock'] ?> <?= $p['unit'] ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="currentStockInfo" style="display:none;background:var(--dark);padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13px;font-weight:700">
          Stok saat ini: <span id="currentStockVal" class="text-yellow"></span> <span id="currentStockUnit" class="text-muted"></span>
        </div>
        <div class="form-group">
          <label class="form-label">Tipe Perubahan *</label>
          <select name="type" id="stockType" class="form-select" required onchange="toggleStockLabel()">
            <option value="in">➕ Tambah Stok (Masuk)</option>
            <option value="adjustment">🔄 Sesuaikan Stok (Set Nilai Tepat)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="stockQtyLabel">Jumlah Ditambahkan *</label>
          <input type="number" name="qty" id="stockQty" class="form-control" placeholder="0" min="1" required>
        </div>
        <div class="form-group">
          <label class="form-label">Keterangan</label>
          <input type="text" name="note" class="form-control" placeholder="Contoh: Restock dari supplier...">
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-1">
            <i class="fa-solid fa-floppy-disk"></i> Simpan
          </button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('stockModal')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function updateCurrentStock() {
  var sel  = document.getElementById('stockProductId');
  var opt  = sel.options[sel.selectedIndex];
  var info = document.getElementById('currentStockInfo');
  if (sel.value && opt.dataset.stock !== undefined) {
    document.getElementById('currentStockVal').textContent  = opt.dataset.stock;
    document.getElementById('currentStockUnit').textContent = opt.dataset.unit || '';
    info.style.display = 'block';
  } else {
    info.style.display = 'none';
  }
}

function toggleStockLabel() {
  var type  = document.getElementById('stockType').value;
  var label = document.getElementById('stockQtyLabel');
  var input = document.getElementById('stockQty');
  if (type === 'in') {
    label.textContent    = 'Jumlah Ditambahkan *';
    input.placeholder    = '0';
    input.min            = '1';
  } else {
    label.textContent    = 'Set Nilai Stok Baru *';
    input.placeholder    = 'Nilai stok yang benar';
    input.min            = '0';
  }
}
</script>

<?php include '../includes/footer.php'; ?>

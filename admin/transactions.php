<?php
require_once '../config/config.php';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Handle void/delete transaksi
$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $msg = 'Sesi tidak valid, coba lagi.'; $msgType = 'danger'; }
    else {
    $action = $_POST['action'] ?? '';
    if ($action === 'void') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $db->beginTransaction();
                // Lock the transaction row
              $checkStmt = $db->prepare("SELECT invoice_no, status FROM transactions WHERE id=? FOR UPDATE");
                $checkStmt->execute([$id]);
              $trx = $checkStmt->fetch();
              if ($trx && $trx['status'] !== 'void') {
                    // Kembalikan stok
                $items = $db->prepare("SELECT product_id, product_name, qty FROM transaction_items WHERE transaction_id=?");
                    $items->execute([$id]);
                $productLockStmt = $db->prepare("SELECT stock FROM products WHERE id=? FOR UPDATE");
                $updateStockStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id=?");
                $historyStmt = $db->prepare("INSERT INTO stock_history (product_id, type, qty, stock_before, stock_after, note, reference, user_id) VALUES (?, 'in', ?, ?, ?, ?, ?, ?)");
                    foreach ($items->fetchAll() as $item) {
                  $productId = (int)($item['product_id'] ?? 0);
                  if ($productId <= 0) {
                    continue;
                  }
                  $qty = (int)$item['qty'];
                  $productLockStmt->execute([$productId]);
                  $stockBefore = $productLockStmt->fetchColumn();
                  if ($stockBefore === false) {
                    continue;
                  }
                  $stockBefore = (int)$stockBefore;
                  $stockAfter = $stockBefore + $qty;

                  $updateStockStmt->execute([$qty, $productId]);
                  $historyStmt->execute([
                    $productId,
                    $qty,
                    $stockBefore,
                    $stockAfter,
                    'Void transaksi',
                    $trx['invoice_no'],
                    $_SESSION['user_id'] ?? null,
                  ]);
                    }
                    $db->prepare("UPDATE transactions SET status='void' WHERE id=?")->execute([$id]);
                    $db->commit();
                    $msg = 'Transaksi berhasil dibatalkan dan stok dikembalikan!';
                } else {
                    $db->rollBack();
                    $msg = 'Transaksi sudah di-void sebelumnya.';
                    $msgType = 'danger';
                }
            } catch (Exception $e) {
                $db->rollBack();
                $msg = 'Gagal membatalkan transaksi. Silakan coba lagi.';
                $msgType = 'danger';
            }
        }
    }
    } // end csrf check
}

// Filters
$search     = sanitize($_GET['search'] ?? '');
$dateFrom   = $_GET['date_from'] ?? '';
$dateTo     = $_GET['date_to'] ?? '';
$method     = $_GET['method'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 15;
$offset     = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($search)  { $where[] = '(t.invoice_no LIKE ? OR t.customer_name LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
if ($dateFrom){ $where[] = 'DATE(t.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)  { $where[] = 'DATE(t.created_at) <= ?'; $params[] = $dateTo; }
if ($method)  { $where[] = 't.payment_method = ?'; $params[] = $method; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM transactions t WHERE " . implode(' AND ', $where));
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $limit);

$stmt = $db->prepare("SELECT t.*, u.full_name as cashier_name
    FROM transactions t LEFT JOIN users u ON t.cashier_id=u.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY t.created_at DESC LIMIT {$limit} OFFSET {$offset}");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Total filter
$sumStmt = $db->prepare("SELECT COALESCE(SUM(total),0) as total_revenue, COUNT(*) as total_count FROM transactions t WHERE " . implode(' AND ', $where) . " AND t.status != 'void'");
$sumStmt->execute($params);
$summary = $sumStmt->fetch();

$pageTitle = 'Transaksi - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div>
      <div class="page-title">Riwayat Transaksi</div>
      <div class="page-subtitle"><?= number_format($totalRows) ?> transaksi ditemukan</div>
    </div>
    <div class="d-flex gap-2">
      <span class="badge badge-success" style="font-size:14px;padding:8px 14px">
        Total: <?= formatRupiah((float)$summary['total_revenue']) ?>
      </span>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> mb-3">
    <i class="fa-solid fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Filter -->
  <div class="card mb-3" style="padding:14px">
    <form method="GET" class="d-flex gap-2 flex-wrap align-center">
      <div class="search-wrap" style="min-width:180px;flex:1">
        <i class="fa-solid fa-search"></i>
        <input type="text" name="search" class="form-control search-input" placeholder="No. invoice / nama customer..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <input type="date" name="date_from" class="form-control" style="width:140px" value="<?= htmlspecialchars($dateFrom) ?>" title="Dari tanggal">
      <input type="date" name="date_to" class="form-control" style="width:140px" value="<?= htmlspecialchars($dateTo) ?>" title="Sampai tanggal">
      <select name="method" class="form-select" style="width:140px">
        <option value="">Semua Metode</option>
        <option value="tunai" <?= $method==='tunai'?'selected':'' ?>>Tunai</option>
        <option value="transfer" <?= $method==='transfer'?'selected':'' ?>>Transfer</option>
      </select>
      <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="transactions.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i></a>
    </form>
  </div>

  <div class="card">
    <div class="desktop-table">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>No. Invoice</th>
            <th>Customer</th>
            <th>Kasir</th>
            <th class="text-right">Total</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Waktu</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="8"><div class="empty-state" style="padding:30px"><i class="fa-solid fa-receipt"></i><p>Tidak ada transaksi</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($transactions as $t): ?>
        <?php $isVoid = ($t['status'] ?? '') === 'void'; ?>
        <tr style="<?= $isVoid ? 'opacity:.5' : '' ?>">
          <td class="fw-bold" style="font-family:monospace"><?= htmlspecialchars($t['invoice_no']) ?></td>
          <td><?= htmlspecialchars($t['customer_name'] ?: '-') ?></td>
          <td><?= htmlspecialchars($t['cashier_name'] ?? '-') ?></td>
          <td class="text-right fw-bolder text-yellow"><?= formatRupiah((float)$t['total']) ?></td>
          <td>
            <span class="badge <?= $t['payment_method']==='tunai'?'badge-success':'badge-info' ?>">
              <?= $t['payment_method']==='tunai' ? '💵 Tunai' : '📱 Transfer' ?>
            </span>
          </td>
          <td>
            <?php if ($isVoid): ?>
            <span class="badge badge-danger">Void</span>
            <?php else: ?>
            <span class="badge badge-success">Selesai</span>
            <?php endif; ?>
          </td>
          <td class="text-muted" style="font-size:12px">
            <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-info" onclick="viewTransaction(<?= $t['id'] ?>)" title="Detail">
                <i class="fa-solid fa-eye"></i>
              </button>
              <?php if (!$isVoid): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Batalkan transaksi ini? Stok akan dikembalikan.')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="void">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" title="Batalkan">
                  <i class="fa-solid fa-ban"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    </div>
    <!-- Mobile Cards -->
    <div class="mobile-cards" style="padding:12px">
      <?php if (empty($transactions)): ?>
      <div class="empty-state" style="padding:30px"><i class="fa-solid fa-receipt"></i><p>Tidak ada transaksi</p></div>
      <?php else: ?>
      <?php foreach ($transactions as $t): ?>
      <?php $isVoid = ($t['status'] ?? '') === 'void'; ?>
      <div class="m-card <?= $isVoid ? 'void' : '' ?>" style="flex-direction:column;align-items:stretch;gap:8px">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="m-card-icon">
            <i class="fa-solid fa-receipt" style="color:var(--yellow)"></i>
          </div>
          <div class="m-card-body">
            <div class="m-card-title" style="font-family:monospace"><?= htmlspecialchars($t['invoice_no']) ?></div>
            <div class="m-card-sub">
              <span><?= htmlspecialchars($t['customer_name'] ?: '-') ?></span>
              <span>·</span>
              <span><?= htmlspecialchars($t['cashier_name'] ?? '-') ?></span>
              <span>·</span>
              <span><?= date('d/m H:i', strtotime($t['created_at'])) ?></span>
            </div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value text-yellow" style="font-size:13px"><?= formatRupiah((float)$t['total']) ?></div>
            <div style="display:flex;gap:4px;align-items:center">
              <span class="badge <?= $t['payment_method']==='tunai'?'badge-success':'badge-info' ?>" style="font-size:9px;padding:1px 5px">
                <?= $t['payment_method']==='tunai' ? 'Tunai' : 'Transfer' ?>
              </span>
              <?php if ($isVoid): ?>
              <span class="badge badge-danger" style="font-size:9px;padding:1px 5px">Void</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="m-card-actions">
          <button class="btn btn-sm btn-info" onclick="viewTransaction(<?= $t['id'] ?>)">
            <i class="fa-solid fa-eye"></i> Detail
          </button>
          <?php if (!$isVoid): ?>
          <form method="POST" style="display:contents" onsubmit="return confirm('Batalkan transaksi ini?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="void">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i> Void</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="padding:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-top:1px solid var(--border)">
      <span class="text-muted" style="font-size:13px">Halaman <?= $page ?> dari <?= $totalPages ?></span>
      <div class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a class="page-btn <?= $i==$page?'active':'' ?>"
           href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&method=<?= urlencode($method) ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<!-- Modal Detail Transaksi -->
<div class="modal-overlay" id="txModal" style="display:none">
  <div class="modal-box" style="max-width:500px">
    <div class="modal-header">
      <span class="modal-title"><i class="fa-solid fa-receipt" style="color:var(--yellow)"></i> Detail Transaksi</span>
      <button class="modal-close" onclick="closeModal('txModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div id="txDetail"><div style="text-align:center;padding:30px"><div class="spinner"></div></div></div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary flex-1" onclick="printTx()">
          <i class="fa-solid fa-print"></i> Cetak Struk
        </button>
        <button class="btn btn-secondary" onclick="closeModal('txModal')">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewTransaction(id) {
  document.getElementById('txDetail').innerHTML = '<div style="text-align:center;padding:30px"><div class="spinner"></div></div>';
  openModal('txModal');
  fetch(BASE_URL + '/api/get_transaction.php?id=' + id)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('txDetail').innerHTML = buildInvoiceHtml(data.transaction);
      } else {
        document.getElementById('txDetail').innerHTML = '<p class="text-danger">Gagal memuat data transaksi.</p>';
      }
    })
    .catch(() => {
      document.getElementById('txDetail').innerHTML = '<p class="text-danger">Koneksi gagal.</p>';
    });
}

function printTx() {
  var printArea = document.querySelector('#txDetail #printArea');
  if (!printArea) { showToast('Data struk belum siap', 'warning'); return; }
  var paperSize = printArea.dataset.paper || '80';
  printReceipt(printArea.outerHTML, paperSize);
}
</script>

<?php include '../includes/footer.php'; ?>

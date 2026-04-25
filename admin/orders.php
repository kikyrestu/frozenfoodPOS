<?php
declare(strict_types=1);

require_once '../config/config.php';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Filters
$search   = sanitize($_GET['search'] ?? '');
$status   = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 15;
$offset   = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];

if ($search)   { $where[] = '(o.order_no LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
if ($status)   { $where[] = 'o.status = ?'; $params[] = $status; }
if ($dateFrom) { $where[] = 'DATE(o.created_at) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(o.created_at) <= ?'; $params[] = $dateTo; }

$wClause = implode(' AND ', $where);

$totalStmt = $db->prepare("SELECT COUNT(*) FROM online_orders o WHERE {$wClause}");
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $limit);

$stmt = $db->prepare("SELECT o.* FROM online_orders o WHERE {$wClause} ORDER BY o.created_at DESC LIMIT {$limit} OFFSET {$offset}");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Summary counts
$cntStmt = $db->query("SELECT status, COUNT(*) as cnt FROM online_orders GROUP BY status");
$counts  = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
foreach ($cntStmt->fetchAll() as $r) {
    $counts[$r['status']] = (int)$r['cnt'];
}

$pageTitle = 'Pesanan Online - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div>
      <div class="page-title">Pesanan Online</div>
      <div class="page-subtitle"><?= number_format($totalRows) ?> pesanan ditemukan</div>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <span class="badge" style="font-size:13px;padding:8px 14px;background:var(--warning-bg,#fff3cd);color:#856404">
      ⏳ Pending: <?= $counts['pending'] ?>
    </span>
    <span class="badge badge-info" style="font-size:13px;padding:8px 14px">
      ✅ Approved: <?= $counts['approved'] ?>
    </span>
    <span class="badge badge-danger" style="font-size:13px;padding:8px 14px">
      ❌ Rejected: <?= $counts['rejected'] ?>
    </span>
    <span class="badge badge-success" style="font-size:13px;padding:8px 14px">
      🎉 Completed: <?= $counts['completed'] ?>
    </span>
  </div>

  <!-- Filter -->
  <div class="card mb-3" style="padding:14px">
    <form method="GET" class="d-flex gap-2 flex-wrap align-center">
      <div class="search-wrap" style="min-width:180px;flex:1">
        <i class="fa-solid fa-search"></i>
        <input type="text" name="search" class="form-control search-input" placeholder="No. order / nama / HP..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="status" class="form-select" style="width:140px">
        <option value="">Semua Status</option>
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
        <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
        <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
        <option value="completed" <?= $status==='completed'?'selected':'' ?>>Completed</option>
      </select>
      <input type="date" name="date_from" class="form-control" style="width:140px" value="<?= htmlspecialchars($dateFrom) ?>" title="Dari tanggal">
      <input type="date" name="date_to" class="form-control" style="width:140px" value="<?= htmlspecialchars($dateTo) ?>" title="Sampai tanggal">
      <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="orders.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i></a>
    </form>
  </div>

  <!-- Orders Table -->
  <div class="card">
    <div class="desktop-table">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>No. Order</th>
            <th>Customer</th>
            <th>Telepon</th>
            <th class="text-right">Total</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Waktu</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="8"><div class="empty-state" style="padding:30px"><i class="fa-solid fa-shopping-bag"></i><p>Tidak ada pesanan</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="fw-bold" style="font-family:monospace"><?= htmlspecialchars($o['order_no']) ?></td>
          <td><?= htmlspecialchars($o['customer_name']) ?></td>
          <td><?= htmlspecialchars($o['customer_phone']) ?></td>
          <td class="text-right fw-bolder text-yellow"><?= formatRupiah((float)$o['total']) ?></td>
          <td>
            <span class="badge <?= $o['payment_method']==='qris'?'badge-info':'badge-success' ?>">
              <?= $o['payment_method']==='qris' ? '📱 QRIS' : '🏦 Bank' ?>
            </span>
          </td>
          <td>
            <?php
            $statusBadge = match($o['status']) {
                'pending'   => '<span class="badge" style="background:#fff3cd;color:#856404">⏳ Pending</span>',
                'approved'  => '<span class="badge badge-info">✅ Approved</span>',
                'rejected'  => '<span class="badge badge-danger">❌ Rejected</span>',
                'completed' => '<span class="badge badge-success">🎉 Completed</span>',
                default     => '<span class="badge">' . htmlspecialchars($o['status']) . '</span>',
            };
            echo $statusBadge;
            ?>
          </td>
          <td class="text-muted" style="font-size:12px">
            <?= $o['created_at'] ? date('d/m/Y H:i', strtotime($o['created_at'])) : '-' ?>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-info" onclick="viewOrder(<?= $o['id'] ?>)" title="Detail">
                <i class="fa-solid fa-eye"></i>
              </button>
              <?php if ($o['status'] === 'pending'): ?>
              <button class="btn btn-sm btn-success" onclick="updateOrder(<?= $o['id'] ?>, 'approve')" title="Approve">
                <i class="fa-solid fa-check"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="updateOrder(<?= $o['id'] ?>, 'reject')" title="Reject">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <?php elseif ($o['status'] === 'approved'): ?>
              <button class="btn btn-sm btn-success" onclick="updateOrder(<?= $o['id'] ?>, 'complete')" title="Selesai">
                <i class="fa-solid fa-flag-checkered"></i>
              </button>
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
      <?php if (empty($orders)): ?>
      <div class="empty-state" style="padding:30px"><i class="fa-solid fa-shopping-bag"></i><p>Tidak ada pesanan</p></div>
      <?php else: ?>
      <?php foreach ($orders as $o): ?>
      <?php
        $mStatusBadge = match($o['status']) {
            'pending'   => '<span class="badge" style="background:#fff3cd;color:#856404;font-size:9px;padding:1px 5px">Pending</span>',
            'approved'  => '<span class="badge badge-info" style="font-size:9px;padding:1px 5px">Approved</span>',
            'rejected'  => '<span class="badge badge-danger" style="font-size:9px;padding:1px 5px">Rejected</span>',
            'completed' => '<span class="badge badge-success" style="font-size:9px;padding:1px 5px">Completed</span>',
            default     => '<span class="badge" style="font-size:9px;padding:1px 5px">' . htmlspecialchars($o['status']) . '</span>',
        };
      ?>
      <div class="m-card" style="flex-direction:column;align-items:stretch;gap:8px">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="m-card-icon">
            <i class="fa-solid fa-shopping-bag" style="color:var(--yellow)"></i>
          </div>
          <div class="m-card-body">
            <div class="m-card-title" style="font-family:monospace"><?= htmlspecialchars($o['order_no']) ?></div>
            <div class="m-card-sub">
              <span><?= htmlspecialchars($o['customer_name']) ?></span>
              <span>·</span>
              <span><?= htmlspecialchars($o['customer_phone']) ?></span>
              <span>·</span>
              <span><?= $o['created_at'] ? date('d/m H:i', strtotime($o['created_at'])) : '-' ?></span>
            </div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value text-yellow" style="font-size:13px"><?= formatRupiah((float)$o['total']) ?></div>
            <div style="display:flex;gap:4px;align-items:center">
              <span class="badge <?= $o['payment_method']==='qris'?'badge-info':'badge-success' ?>" style="font-size:9px;padding:1px 5px">
                <?= $o['payment_method']==='qris' ? 'QRIS' : 'Bank' ?>
              </span>
              <?= $mStatusBadge ?>
            </div>
          </div>
        </div>
        <div class="m-card-actions">
          <button class="btn btn-sm btn-info" onclick="viewOrder(<?= $o['id'] ?>)">
            <i class="fa-solid fa-eye"></i> Detail
          </button>
          <?php if ($o['status'] === 'pending'): ?>
          <button class="btn btn-sm btn-success" onclick="updateOrder(<?= $o['id'] ?>, 'approve')">
            <i class="fa-solid fa-check"></i> Approve
          </button>
          <button class="btn btn-sm btn-danger" onclick="updateOrder(<?= $o['id'] ?>, 'reject')">
            <i class="fa-solid fa-xmark"></i> Reject
          </button>
          <?php elseif ($o['status'] === 'approved'): ?>
          <button class="btn btn-sm btn-success" onclick="updateOrder(<?= $o['id'] ?>, 'complete')">
            <i class="fa-solid fa-flag-checkered"></i> Selesai
          </button>
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
        <a class="page-btn <?= $i===$page?'active':'' ?>"
           href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<!-- Modal Detail Order -->
<div class="modal-overlay" id="orderModal" style="display:none">
  <div class="modal-box" style="max-width:550px">
    <div class="modal-header">
      <span class="modal-title"><i class="fa-solid fa-shopping-bag" style="color:var(--yellow)"></i> Detail Pesanan</span>
      <button class="modal-close" onclick="closeModal('orderModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div id="orderDetail"><div style="text-align:center;padding:30px"><div class="spinner"></div></div></div>
    </div>
  </div>
</div>

<!-- Modal Admin Note -->
<div class="modal-overlay" id="noteModal" style="display:none">
  <div class="modal-box" style="max-width:400px">
    <div class="modal-header">
      <span class="modal-title" id="noteModalTitle">Konfirmasi</span>
      <button class="modal-close" onclick="closeModal('noteModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="noteOrderId">
      <input type="hidden" id="noteAction">
      <div class="form-group">
        <label class="form-label">Catatan Admin (opsional)</label>
        <textarea id="noteText" class="form-control" rows="3" placeholder="Tambahkan catatan..."></textarea>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary flex-1" onclick="confirmAction()">
          <i class="fa-solid fa-check"></i> Konfirmasi
        </button>
        <button class="btn btn-secondary" onclick="closeModal('noteModal')">Batal</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewOrder(id) {
  document.getElementById('orderDetail').innerHTML = '<div style="text-align:center;padding:30px"><div class="spinner"></div></div>';
  openModal('orderModal');
  fetch(BASE_URL + '/api/get_order.php?id=' + id)
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        document.getElementById('orderDetail').innerHTML = '<p class="text-danger">Gagal memuat data.</p>';
        return;
      }
      const o = data.order;
      const statusMap = {
        pending: '<span class="badge" style="background:#fff3cd;color:#856404">⏳ Pending</span>',
        approved: '<span class="badge badge-info">✅ Approved</span>',
        rejected: '<span class="badge badge-danger">❌ Rejected</span>',
        completed: '<span class="badge badge-success">🎉 Completed</span>'
      };
      let html = `
        <div style="margin-bottom:12px">
          <div style="font-family:monospace;font-size:16px;font-weight:bold;margin-bottom:4px">${o.order_no}</div>
          ${statusMap[o.status] || o.status}
          <div class="text-muted" style="font-size:12px;margin-top:4px">${o.created_at}</div>
        </div>
        <hr style="border:none;border-top:1px dashed var(--border);margin:10px 0">
        <div style="font-size:13px;margin-bottom:8px">
          <strong>Customer:</strong> ${escHtml(o.customer_name)}<br>
          <strong>Telepon:</strong> ${escHtml(o.customer_phone)}<br>
          <strong>Alamat:</strong> ${escHtml(o.customer_address)}<br>
          <strong>Metode:</strong> ${o.payment_method === 'qris' ? '📱 QRIS' : '🏦 Bank Transfer'}
        </div>`;

      if (o.proof_image) {
        html += `<div style="margin:8px 0"><strong style="font-size:13px">Bukti Pembayaran:</strong><br>
          <a href="${o.proof_image}" target="_blank"><img src="${o.proof_image}" style="max-width:100%;max-height:200px;border-radius:8px;margin-top:4px;border:1px solid var(--border)"></a></div>`;
      }

      html += '<hr style="border:none;border-top:1px dashed var(--border);margin:10px 0">';
      html += '<div style="font-size:13px"><strong>Item Pesanan:</strong></div>';
      html += '<table style="width:100%;font-size:12px;margin-top:6px"><thead><tr><th style="text-align:left">Produk</th><th style="text-align:center">Qty</th><th style="text-align:right">Subtotal</th></tr></thead><tbody>';
      (o.items || []).forEach(item => {
        html += `<tr><td>${escHtml(item.product_name)}</td><td style="text-align:center">${item.qty}</td><td style="text-align:right">${formatRp(item.subtotal)}</td></tr>`;
      });
      html += `</tbody><tfoot><tr><td colspan="2" style="text-align:right;font-weight:bold;padding-top:8px">Total:</td><td style="text-align:right;font-weight:bold;padding-top:8px;color:var(--yellow)">${formatRp(o.total)}</td></tr></tfoot></table>`;

      if (o.admin_note) {
        html += `<hr style="border:none;border-top:1px dashed var(--border);margin:10px 0"><div style="font-size:13px"><strong>Catatan Admin:</strong><br>${escHtml(o.admin_note)}</div>`;
      }

      // Action buttons
      if (o.status === 'pending') {
        html += `<div class="d-flex gap-2 mt-3">
          <button class="btn btn-success flex-1" onclick="closeModal('orderModal');updateOrder(${o.id},'approve')"><i class="fa-solid fa-check"></i> Approve</button>
          <button class="btn btn-danger flex-1" onclick="closeModal('orderModal');updateOrder(${o.id},'reject')"><i class="fa-solid fa-xmark"></i> Reject</button>
        </div>`;
      } else if (o.status === 'approved') {
        html += `<div class="d-flex gap-2 mt-3">
          <button class="btn btn-success flex-1" onclick="closeModal('orderModal');updateOrder(${o.id},'complete')"><i class="fa-solid fa-flag-checkered"></i> Selesaikan</button>
        </div>`;
      }

      document.getElementById('orderDetail').innerHTML = html;
    })
    .catch(() => {
      document.getElementById('orderDetail').innerHTML = '<p class="text-danger">Koneksi gagal.</p>';
    });
}

function updateOrder(id, action) {
  const titles = {approve:'Approve Pesanan',reject:'Tolak Pesanan',complete:'Selesaikan Pesanan'};
  document.getElementById('noteModalTitle').textContent = titles[action] || 'Konfirmasi';
  document.getElementById('noteOrderId').value = id;
  document.getElementById('noteAction').value = action;
  document.getElementById('noteText').value = '';
  openModal('noteModal');
}

function confirmAction() {
  const id     = parseInt(document.getElementById('noteOrderId').value);
  const action = document.getElementById('noteAction').value;
  const note   = document.getElementById('noteText').value.trim();

  const btn = document.querySelector('#noteModal .btn-primary');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:16px;height:16px"></div> Memproses...';

  fetch(BASE_URL + '/api/update_order.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id, action, admin_note: note})
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Konfirmasi';
    closeModal('noteModal');
    if (data.success) {
      showNotif(data.message, 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      showNotif(data.message, 'error');
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Konfirmasi';
    closeModal('noteModal');
    showNotif('Koneksi gagal!', 'error');
  });
}

function showNotif(msg, type) {
  const existing = document.querySelector('.alert-floating');
  if (existing) existing.remove();
  const cls = type === 'success' ? 'alert-success' : 'alert-danger';
  const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
  const div = document.createElement('div');
  div.className = `alert ${cls} alert-floating`;
  div.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:280px;animation:slideIn .3s ease';
  div.innerHTML = `<i class="fa-solid fa-${icon}"></i> ${escHtml(msg)}`;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 3000);
}
</script>

<?php include '../includes/footer.php'; ?>

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

    if ($action === 'save') {
        $id         = (int)($_POST['id'] ?? 0);
        $name       = sanitize($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price      = (float)($_POST['price'] ?? 0);
        $stock      = (int)($_POST['stock'] ?? 0);
        $unit       = sanitize($_POST['unit'] ?? 'pcs');
        $status     = (int)($_POST['status'] ?? 1);
        $lowAlert   = (int)($_POST['low_stock_alert'] ?? 5);

        if (empty($name) || $price <= 0) {
            $msg = 'Nama dan harga wajib diisi!';
            $msgType = 'danger';
        } else {
            $imageName = $_POST['existing_image'] ?? null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $result = secureImageUpload($_FILES['image'], 'products', 'product');
                if (!$result['success']) {
                    $msg = $result['message']; $msgType = 'danger';
                } else {
                    if ($imageName && file_exists(UPLOAD_PATH . 'products/' . $imageName)) {
                        unlink(UPLOAD_PATH . 'products/' . $imageName);
                    }
                    $imageName = $result['filename'];
                }
            }

            if (empty($msg)) {
                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE products SET name=?,category_id=?,price=?,stock=?,unit=?,status=?,low_stock_alert=?,image=?,updated_at=NOW() WHERE id=?");
                    $stmt->execute([$name, $categoryId ?: null, $price, $stock, $unit, $status, $lowAlert, $imageName, $id]);
                    $msg = 'Produk berhasil diperbarui!';
                } else {
                    $stmt = $db->prepare("INSERT INTO products (name,category_id,price,stock,unit,status,low_stock_alert,image) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$name, $categoryId ?: null, $price, $stock, $unit, $status, $lowAlert, $imageName]);
                    $msg = 'Produk berhasil ditambahkan!';
                }
            }
        }
    }

    if ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT image FROM products WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && $row['image'] && file_exists(UPLOAD_PATH . 'products/' . $row['image'])) {
            unlink(UPLOAD_PATH . 'products/' . $row['image']);
        }
        $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        $msg = 'Produk berhasil dihapus!';
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE products SET status = 1 - status WHERE id=?")->execute([$id]);
        $msg = 'Status produk diperbarui!';
    }
    } // end csrf check
}

$search    = sanitize($_GET['search'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 12;
$offset    = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($search)    { $where[] = 'p.name LIKE ?';     $params[] = "%{$search}%"; }
if ($catFilter) { $where[] = 'p.category_id = ?'; $params[] = $catFilter; }

$totalStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE " . implode(' AND ', $where));
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $limit);

$stmt = $db->prepare("SELECT p.*, c.name as cat_name FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC LIMIT {$limit} OFFSET {$offset}");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$units = ['pcs', 'pack', 'box', 'kg', 'liter', 'sachet', 'ikat', 'lusin'];

$pageTitle = 'Produk - Admin';
include '../includes/header.php';
?>

<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">

  <div class="page-header">
    <div>
      <div class="page-title">Manajemen Produk</div>
      <div class="page-subtitle"><?= number_format($totalRows) ?> produk terdaftar</div>
    </div>
    <button class="btn btn-primary" onclick="openProductModal()">
      <i class="fa-solid fa-plus"></i> Tambah Produk
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> mb-3">
    <i class="fa-solid fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <div class="card mb-3" style="padding:14px">
    <form method="GET" class="d-flex gap-2 flex-wrap align-center">
      <div class="search-wrap" style="flex:1;min-width:180px">
        <i class="fa-solid fa-search"></i>
        <input type="text" name="search" class="form-control search-input" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <select name="cat" class="form-select" style="width:160px">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
      <a href="products.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i></a>
    </form>
  </div>

  <div class="card">
    <div class="desktop-table">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Produk</th>
            <th>Kategori</th>
            <th class="text-right">Harga</th>
            <th class="text-right">Stok</th>
            <th>Satuan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="7"><div class="empty-state" style="padding:30px"><i class="fa-solid fa-box-open"></i><p>Produk tidak ditemukan</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <div class="d-flex align-center gap-2">
              <?php if ($p['image'] && file_exists(UPLOAD_PATH . 'products/' . $p['image'])): ?>
              <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($p['image']) ?>" class="product-thumb" alt="">
              <?php else: ?>
              <div class="product-no-img"><i class="fa-solid fa-snowflake"></i></div>
              <?php endif; ?>
              <span class="fw-bold"><?= htmlspecialchars($p['name']) ?></span>
            </div>
          </td>
          <td><?= htmlspecialchars($p['cat_name'] ?? '-') ?></td>
          <td class="text-right fw-bolder text-yellow"><?= formatRupiah($p['price']) ?></td>
          <td class="text-right">
            <span class="fw-bold <?= $p['stock']==0?'text-danger':($p['stock']<=$p['low_stock_alert']?'text-warning':'') ?>">
              <?= number_format($p['stock']) ?>
            </span>
          </td>
          <td><?= htmlspecialchars($p['unit']) ?></td>
          <td>
            <span class="badge <?= $p['status']?'badge-success':'badge-danger' ?>">
              <?= $p['status']?'Aktif':'Nonaktif' ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-warning" onclick='editProduct(<?= json_encode($p, JSON_HEX_QUOT | JSON_HEX_TAG) ?>)'>
                <i class="fa-solid fa-pen"></i>
              </button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Toggle status produk ini?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-info"><i class="fa-solid fa-toggle-on"></i></button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus produk ini? Data tidak bisa dikembalikan!')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
              </form>
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
      <?php if (empty($products)): ?>
      <div class="empty-state" style="padding:30px"><i class="fa-solid fa-box-open"></i><p>Produk tidak ditemukan</p></div>
      <?php else: ?>
      <?php foreach ($products as $p): ?>
      <div class="m-card" style="flex-direction:column;align-items:stretch;gap:8px">
        <div style="display:flex;align-items:center;gap:12px">
          <?php if ($p['image'] && file_exists(UPLOAD_PATH . 'products/' . $p['image'])): ?>
          <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($p['image']) ?>" class="m-card-img" alt="">
          <?php else: ?>
          <div class="m-card-icon"><i class="fa-solid fa-snowflake"></i></div>
          <?php endif; ?>
          <div class="m-card-body">
            <div class="m-card-title"><?= htmlspecialchars($p['name']) ?></div>
            <div class="m-card-sub">
              <span><?= htmlspecialchars($p['cat_name'] ?? '-') ?></span>
              <span>·</span>
              <span><?= htmlspecialchars($p['unit']) ?></span>
              <span class="badge <?= $p['status']?'badge-success':'badge-danger' ?>" style="font-size:9px;padding:1px 5px"><?= $p['status']?'Aktif':'Off' ?></span>
            </div>
          </div>
          <div class="m-card-right">
            <div class="m-card-value text-yellow" style="font-size:13px"><?= formatRupiah($p['price']) ?></div>
            <div class="m-card-label">Stok: <strong class="<?= $p['stock']==0?'text-danger':($p['stock']<=$p['low_stock_alert']?'text-warning':'') ?>"><?= number_format($p['stock']) ?></strong></div>
          </div>
        </div>
        <div class="m-card-actions">
          <button class="btn btn-sm btn-warning" onclick='editProduct(<?= json_encode($p, JSON_HEX_QUOT | JSON_HEX_TAG) ?>)'>
            <i class="fa-solid fa-pen"></i> Edit
          </button>
          <form method="POST" style="display:contents" onsubmit="return confirm('Toggle status?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn btn-sm btn-info"><i class="fa-solid fa-toggle-on"></i></button>
          </form>
          <form method="POST" style="display:contents" onsubmit="return confirm('Hapus produk ini?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
          </form>
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
        <a class="page-btn <?= $i==$page?'active':'' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&cat=<?= $catFilter ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>
</div>

<!-- Modal: Product Form -->
<div class="modal-overlay" id="productModal" style="display:none">
  <div class="modal-box" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title" id="productModalTitle"><i class="fa-solid fa-box" style="color:var(--yellow)"></i> Tambah Produk</span>
      <button class="modal-close" onclick="closeModal('productModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form method="POST" enctype="multipart/form-data" id="productForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="productId" value="0">
        <input type="hidden" name="existing_image" id="existingImage" value="">

        <div class="grid-2">
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Nama Produk *</label>
            <input type="text" name="name" id="pName" class="form-control" placeholder="Nama produk..." required>
          </div>
          <div class="form-group">
            <label class="form-label">Kategori</label>
            <select name="category_id" id="pCategory" class="form-select">
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Satuan</label>
            <select name="unit" id="pUnit" class="form-select">
              <?php foreach ($units as $u): ?>
              <option value="<?= $u ?>"><?= $u ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Harga Jual (Rp) *</label>
            <input type="number" name="price" id="pPrice" class="form-control" placeholder="0" min="0" required>
          </div>
          <div class="form-group">
            <label class="form-label">Stok</label>
            <input type="number" name="stock" id="pStock" class="form-control" placeholder="0" min="0" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Alert Stok Menipis</label>
            <input type="number" name="low_stock_alert" id="pLowAlert" class="form-control" value="5" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="pStatus" class="form-select">
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Foto Produk (JPG/PNG, Maks 2MB)</label>
            <input type="file" name="image" id="pImage" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div id="imagePreview" style="margin-top:10px"></div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-2">
          <button type="submit" class="btn btn-primary flex-1">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Produk
          </button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('productModal')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openProductModal() {
  document.getElementById('productModalTitle').innerHTML = '<i class="fa-solid fa-plus" style="color:var(--yellow)"></i> Tambah Produk';
  document.getElementById('productForm').reset();
  document.getElementById('productId').value = 0;
  document.getElementById('pStock').value = 0;
  document.getElementById('pLowAlert').value = 5;
  document.getElementById('existingImage').value = '';
  document.getElementById('imagePreview').innerHTML = '';
  openModal('productModal');
}

function editProduct(p) {
  document.getElementById('productModalTitle').innerHTML = '<i class="fa-solid fa-pen" style="color:var(--yellow)"></i> Edit Produk';
  document.getElementById('productId').value = p.id;
  document.getElementById('pName').value = p.name;
  document.getElementById('pCategory').value = p.category_id || '';
  document.getElementById('pPrice').value = p.price;
  document.getElementById('pStock').value = p.stock;
  document.getElementById('pUnit').value = p.unit;
  document.getElementById('pStatus').value = p.status;
  document.getElementById('pLowAlert').value = p.low_stock_alert;
  document.getElementById('existingImage').value = p.image || '';
  var prev = document.getElementById('imagePreview');
  if (p.image) {
    prev.innerHTML = '<img src="' + BASE_URL + '/uploads/products/' + p.image + '" style="height:60px;border-radius:8px;border:2px solid var(--border)" onerror="this.style.display=\'none\'">';
  } else {
    prev.innerHTML = '';
  }
  openModal('productModal');
}

document.getElementById('pImage').addEventListener('change', function() {
  var file = this.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('imagePreview').innerHTML = '<img src="' + e.target.result + '" style="height:60px;border-radius:8px;border:2px solid var(--border)">';
  };
  reader.readAsDataURL(file);
});
</script>

<?php include '../includes/footer.php'; ?>

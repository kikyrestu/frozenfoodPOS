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
        $id   = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        if (empty($name)) {
            $msg = 'Nama kategori wajib diisi!'; $msgType = 'danger';
        } else {
            // Cek duplikat nama
            $check = $db->prepare("SELECT id FROM categories WHERE name=? AND id != ?");
            $check->execute([$name, $id]);
            if ($check->fetch()) {
                $msg = 'Nama kategori sudah ada!'; $msgType = 'danger';
            } else {
                if ($id > 0) {
                    $db->prepare("UPDATE categories SET name=? WHERE id=?")->execute([$name, $id]);
                    $msg = 'Kategori berhasil diperbarui!';
                } else {
                    $db->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
                    $msg = 'Kategori berhasil ditambahkan!';
                }
            }
        }
    }

    if ($action === 'delete') {
        $id    = (int)($_POST['id'] ?? 0);
        $count = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
        $count->execute([$id]);
        if ((int)$count->fetchColumn() > 0) {
            $msg = 'Tidak bisa hapus kategori yang masih memiliki produk!'; $msgType = 'danger';
        } else {
            $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
            $msg = 'Kategori berhasil dihapus!';
        }
    }
    } // end csrf check
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count
    FROM categories c LEFT JOIN products p ON p.category_id=c.id
    GROUP BY c.id ORDER BY c.name")->fetchAll();

$pageTitle = 'Kategori - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div>
      <div class="page-title">Manajemen Kategori</div>
      <div class="page-subtitle"><?= count($categories) ?> kategori terdaftar</div>
    </div>
    <button class="btn btn-primary" onclick="openCatModal()">
      <i class="fa-solid fa-plus"></i> Tambah Kategori
    </button>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> mb-3">
    <i class="fa-solid fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="desktop-table">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nama Kategori</th>
            <th class="text-right">Jumlah Produk</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($categories)): ?>
        <tr><td colspan="3"><div class="empty-state" style="padding:30px"><i class="fa-solid fa-tags"></i><p>Belum ada kategori</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($categories as $c): ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
          <td class="text-right">
            <span class="badge badge-info"><?= $c['product_count'] ?> produk</span>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-warning" onclick='editCat(<?= json_encode($c, JSON_HEX_QUOT | JSON_HEX_TAG) ?>)'>
                <i class="fa-solid fa-pen"></i>
              </button>
              <?php if ((int)$c['product_count'] === 0): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori &quot;<?= htmlspecialchars(addslashes($c['name'])) ?>&quot;?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
              </form>
              <?php else: ?>
              <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa dihapus, masih ada produk">
                <i class="fa-solid fa-lock"></i>
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
      <?php if (empty($categories)): ?>
      <div class="empty-state" style="padding:30px"><i class="fa-solid fa-tags"></i><p>Belum ada kategori</p></div>
      <?php else: ?>
      <?php foreach ($categories as $c): ?>
      <div class="m-card">
        <div class="m-card-icon" style="background:rgba(155,89,182,.15);color:#9b59b6">
          <i class="fa-solid fa-tag"></i>
        </div>
        <div class="m-card-body">
          <div class="m-card-title"><?= htmlspecialchars($c['name']) ?></div>
          <div class="m-card-sub"><span class="badge badge-info" style="font-size:9px;padding:1px 5px"><?= $c['product_count'] ?> produk</span></div>
        </div>
        <div class="m-card-right" style="flex-direction:row;gap:6px">
          <button class="btn btn-sm btn-warning" onclick='editCat(<?= json_encode($c, JSON_HEX_QUOT | JSON_HEX_TAG) ?>)'>
            <i class="fa-solid fa-pen"></i>
          </button>
          <?php if ((int)$c['product_count'] === 0): ?>
          <form method="POST" style="display:contents" onsubmit="return confirm('Hapus kategori?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
          </form>
          <?php else: ?>
          <button class="btn btn-sm btn-secondary" disabled><i class="fa-solid fa-lock"></i></button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<!-- Modal: Category Form -->
<div class="modal-overlay" id="catModal" style="display:none">
  <div class="modal-box" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title" id="catModalTitle">
        <i class="fa-solid fa-tag" style="color:var(--yellow)"></i> Tambah Kategori
      </span>
      <button class="modal-close" onclick="closeModal('catModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form method="POST" id="catForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="catId" value="0">
        <div class="form-group">
          <label class="form-label">Nama Kategori *</label>
          <input type="text" name="name" id="catName" class="form-control"
                 placeholder="Contoh: Nugget, Sosis, Dimsum..." required>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-1">
            <i class="fa-solid fa-floppy-disk"></i> Simpan
          </button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('catModal')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCatModal() {
  document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-plus" style="color:var(--yellow)"></i> Tambah Kategori';
  document.getElementById('catId').value = 0;
  document.getElementById('catName').value = '';
  document.getElementById('catName').focus();
  openModal('catModal');
}

function editCat(c) {
  document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-pen" style="color:var(--yellow)"></i> Edit Kategori';
  document.getElementById('catId').value = c.id;
  document.getElementById('catName').value = c.name;
  openModal('catModal');
}
</script>

<?php include '../includes/footer.php'; ?>

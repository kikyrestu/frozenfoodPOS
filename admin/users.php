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
        $id       = (int)($_POST['id'] ?? 0);
        $username = sanitize($_POST['username'] ?? '');
        $fullName = sanitize($_POST['full_name'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin','kasir']) ? $_POST['role'] : 'kasir';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($fullName)) {
            $msg = 'Username dan nama wajib diisi!'; $msgType = 'danger';
        } else {
            $check = $db->prepare("SELECT id FROM users WHERE username=? AND id != ?");
            $check->execute([$username, $id]);
            if ($check->fetch()) {
                $msg = 'Username sudah digunakan!'; $msgType = 'danger';
            } elseif ($id <= 0 && empty($password)) {
                $msg = 'Password wajib diisi untuk pengguna baru!'; $msgType = 'danger';
            } else {
                if ($id > 0) {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $db->prepare("UPDATE users SET username=?,full_name=?,role=?,password=? WHERE id=?")->execute([$username,$fullName,$role,$hash,$id]);
                    } else {
                        $db->prepare("UPDATE users SET username=?,full_name=?,role=? WHERE id=?")->execute([$username,$fullName,$role,$id]);
                    }
                    $msg = 'Pengguna berhasil diperbarui!';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $db->prepare("INSERT INTO users (username,full_name,role,password) VALUES (?,?,?,?)")->execute([$username,$fullName,$role,$hash]);
                    $msg = 'Pengguna berhasil ditambahkan!';
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            $msg = 'Tidak bisa menghapus akun sendiri!'; $msgType = 'danger';
        } else {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            $msg = 'Pengguna berhasil dihapus!';
        }
    }

    if ($action === 'reset_password') {
        $id          = (int)($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        if (empty($newPassword) || strlen($newPassword) < 4) {
            $msg = 'Password minimal 4 karakter!'; $msgType = 'danger';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $id]);
            $msg = 'Password berhasil direset!';
        }
    }
    } // end csrf check
}

$users = $db->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
$pageTitle = 'Pengguna - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div><div class="page-title">Manajemen Pengguna</div>
    <div class="page-subtitle"><?= count($users) ?> pengguna terdaftar</div></div>
    <button class="btn btn-primary" onclick="openUserModal()"><i class="fa-solid fa-plus"></i> Tambah Pengguna</button>
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
          <tr><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>Terdaftar</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
        <tr><td colspan="5"><div class="empty-state" style="padding:30px"><i class="fa-solid fa-users"></i><p>Belum ada pengguna</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><span class="badge <?= $u['role']==='admin'?'badge-warning':'badge-info' ?>"><?= ucfirst($u['role']) ?></span></td>
          <td class="text-muted" style="font-size:12px"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($u, JSON_HEX_QUOT | JSON_HEX_TAG) ?>)' title="Edit">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn btn-sm btn-info" onclick='openResetModal(<?= $u['id'] ?>, "<?= htmlspecialchars(addslashes($u['full_name'])) ?>")' title="Reset Password">
                <i class="fa-solid fa-key"></i>
              </button>
              <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" title="Hapus"><i class="fa-solid fa-trash"></i></button>
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
      <?php if (empty($users)): ?>
      <div class="empty-state" style="padding:30px"><i class="fa-solid fa-users"></i><p>Belum ada pengguna</p></div>
      <?php else: ?>
      <?php foreach ($users as $u): ?>
      <div class="m-card" style="flex-direction:column;align-items:stretch;gap:8px">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="m-card-icon" style="background:<?= $u['role']==='admin'?'rgba(241,196,15,.15)':'rgba(52,152,219,.15)' ?>;color:<?= $u['role']==='admin'?'var(--yellow)':'var(--info)' ?>">
            <i class="fa-solid fa-<?= $u['role']==='admin'?'user-shield':'user' ?>"></i>
          </div>
          <div class="m-card-body">
            <div class="m-card-title"><?= htmlspecialchars($u['full_name']) ?></div>
            <div class="m-card-sub">
              <span>@<?= htmlspecialchars($u['username']) ?></span>
              <span>·</span>
              <span><?= date('d/m/Y', strtotime($u['created_at'])) ?></span>
            </div>
          </div>
          <div class="m-card-right">
            <span class="badge <?= $u['role']==='admin'?'badge-warning':'badge-info' ?>"><?= ucfirst($u['role']) ?></span>
          </div>
        </div>
        <div class="m-card-actions">
          <button class="btn btn-sm btn-warning" onclick='editUser(<?= json_encode($u, JSON_HEX_QUOT | JSON_HEX_TAG) ?>)'>
            <i class="fa-solid fa-pen"></i> Edit
          </button>
          <button class="btn btn-sm btn-info" onclick='openResetModal(<?= $u['id'] ?>, "<?= htmlspecialchars(addslashes($u['full_name'])) ?>")'>
            <i class="fa-solid fa-key"></i> Reset
          </button>
          <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
          <form method="POST" style="display:contents" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars(addslashes($u['full_name'])) ?>?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<!-- Modal: Tambah/Edit User -->
<div class="modal-overlay" id="userModal" style="display:none">
  <div class="modal-box" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title" id="userModalTitle">
        <i class="fa-solid fa-user-plus" style="color:var(--yellow)"></i> Tambah Pengguna
      </span>
      <button class="modal-close" onclick="closeModal('userModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form method="POST" id="userForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="userId" value="0">
        <div class="form-group">
          <label class="form-label">Nama Lengkap *</label>
          <input type="text" name="full_name" id="uFullName" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input type="text" name="username" id="uUsername" class="form-control" required autocomplete="off">
        </div>
        <div class="form-group">
          <label class="form-label" id="passLabel">Password *</label>
          <div style="position:relative">
            <input type="password" name="password" id="uPassword" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah" autocomplete="new-password">
            <button type="button" onclick="togglePassVis('uPassword')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
              <i class="fa-solid fa-eye" id="uPasswordEye"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" id="uRole" class="form-select">
            <option value="kasir">Kasir</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-1">
            <i class="fa-solid fa-floppy-disk"></i> Simpan
          </button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal-overlay" id="resetModal" style="display:none">
  <div class="modal-box" style="max-width:380px">
    <div class="modal-header">
      <span class="modal-title">
        <i class="fa-solid fa-key" style="color:var(--yellow)"></i> Reset Password
      </span>
      <button class="modal-close" onclick="closeModal('resetModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p class="text-muted" style="margin-bottom:14px;font-size:13px">
        Reset password untuk: <strong id="resetUserName"></strong>
      </p>
      <form method="POST" id="resetForm">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="id" id="resetUserId" value="">
        <div class="form-group">
          <label class="form-label">Password Baru *</label>
          <div style="position:relative">
            <input type="password" name="new_password" id="resetPassword" class="form-control" placeholder="Min. 4 karakter" required minlength="4" autocomplete="new-password">
            <button type="button" onclick="togglePassVis('resetPassword')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
              <i class="fa-solid fa-eye" id="resetPasswordEye"></i>
            </button>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-1">
            <i class="fa-solid fa-key"></i> Reset Password
          </button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('resetModal')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openUserModal() {
  document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus" style="color:var(--yellow)"></i> Tambah Pengguna';
  document.getElementById('userForm').reset();
  document.getElementById('userId').value = 0;
  document.getElementById('passLabel').textContent = 'Password *';
  openModal('userModal');
}

function editUser(u) {
  document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-pen" style="color:var(--yellow)"></i> Edit Pengguna';
  document.getElementById('userId').value = u.id;
  document.getElementById('uFullName').value = u.full_name;
  document.getElementById('uUsername').value = u.username;
  document.getElementById('uPassword').value = '';
  document.getElementById('uRole').value = u.role;
  document.getElementById('passLabel').textContent = 'Password (Kosongkan jika tidak ingin mengubah)';
  openModal('userModal');
}

function openResetModal(id, name) {
  document.getElementById('resetUserId').value = id;
  document.getElementById('resetUserName').textContent = name;
  document.getElementById('resetPassword').value = '';
  openModal('resetModal');
}

function togglePassVis(inputId) {
  var input = document.getElementById(inputId);
  var eye   = document.getElementById(inputId + 'Eye');
  if (input.type === 'password') {
    input.type = 'text';
    eye.className = 'fa-solid fa-eye-slash';
  } else {
    input.type = 'password';
    eye.className = 'fa-solid fa-eye';
  }
}
</script>

<?php include '../includes/footer.php'; ?>

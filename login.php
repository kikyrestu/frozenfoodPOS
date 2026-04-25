<?php
require_once 'config/config.php';
if (isLoggedIn()) { header('Location: ' . BASE_URL . '/pos.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $error = 'Sesi tidak valid, coba lagi.'; }
    else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Bersihkan session lama sebelum set session baru
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . BASE_URL . '/pos.php');
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    }
    } // end csrf check
}

$storeName = getSetting('store_name') ?: 'Fun Frozen Food';
$logoUrl   = getLogoUrl();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= htmlspecialchars($storeName) ?></title>
  <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
  <meta name="theme-color" content="#c0392b">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; overflow:hidden; position:relative; }
    .bg-glow { position:fixed; inset:0; z-index:0;
      background: radial-gradient(ellipse 70% 60% at 20% 20%, rgba(192,57,43,.22) 0%, transparent 60%),
                  radial-gradient(ellipse 60% 70% at 80% 80%, rgba(241,196,15,.10) 0%, transparent 60%), var(--darker); }
    .shape { position:absolute; border-radius:50%; opacity:.07; animation:flt 9s ease-in-out infinite; pointer-events:none; }
    .shape-1 { width:320px; height:320px; background:var(--red); top:-110px; left:-110px; }
    .shape-2 { width:200px; height:200px; background:var(--yellow); bottom:-60px; right:-60px; animation-delay:4s; }
    @keyframes flt { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-30px)} }
    .login-card {
      position:relative; z-index:1; background:var(--surface);
      border:1px solid var(--border); border-radius:24px;
      padding:44px 38px; width:100%; max-width:400px; margin:16px;
      box-shadow:0 30px 80px rgba(0,0,0,.5);
      animation:slideUp .5s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes slideUp { from{opacity:0;transform:translateY(40px) scale(.95)} to{opacity:1;transform:none} }
    .logo-area { text-align:center; margin-bottom:24px; }
    .logo-area img { width:120px; height:auto; filter:drop-shadow(0 6px 20px rgba(192,57,43,.4));
      animation:popIn .6s cubic-bezier(.34,1.56,.64,1) .1s both; }
    @keyframes popIn { from{transform:scale(.7) rotate(-5deg);opacity:0} to{transform:none;opacity:1} }
    .store-nm { font-size:20px; font-weight:900; color:var(--yellow); margin-top:10px; }
    .store-sub { font-size:13px; color:var(--text-muted); margin-top:3px; font-weight:600; }
    .divider { height:1px; background:var(--border); margin:22px 0; }
    .err { background:rgba(192,57,43,.14); border:1px solid rgba(192,57,43,.35);
      border-radius:10px; padding:11px 14px; color:#ff7070;
      font-size:13px; font-weight:700; margin-bottom:16px;
      display:flex; align-items:center; gap:8px;
      animation:shake .35s ease; }
    @keyframes shake { 0%,100%{transform:none} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)} }
    .iw { position:relative; }
    .iw i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:14px; transition:color .2s; }
    .iw input { padding-left:44px; }
    .iw input:focus ~ i, .iw:focus-within i { color:var(--red-light); }
    .btn-login {
      width:100%; padding:14px; background:linear-gradient(135deg,var(--red),var(--red-dark));
      border:none; border-radius:12px; color:#fff; font-size:15px; font-weight:900;
      cursor:pointer; font-family:inherit; letter-spacing:.5px;
      box-shadow:0 6px 22px rgba(192,57,43,.35); transition:all .2s;
      display:flex; align-items:center; justify-content:center; gap:8px;
    }
    .btn-login:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(192,57,43,.45); }
    .btn-login:active { transform:none; }
    .login-foot { text-align:center; margin-top:20px; font-size:11px; color:rgba(255,255,255,.15); }
    .hint-box { background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:10px;
      padding:10px 14px; margin-top:16px; font-size:12px; color:var(--text-muted); }
    .hint-box strong { color:var(--text); }
  </style>
</head>
<body>
<div class="bg-glow"></div>
<div class="shape shape-1"></div>
<div class="shape shape-2"></div>

<div class="login-card">
  <div class="logo-area">
    <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" onerror="this.style.display='none'">
    <div class="store-nm"><?= htmlspecialchars($storeName) ?></div>
    <div class="store-sub">Sistem Kasir POS</div>
  </div>
  <div class="divider"></div>

  <?php if ($error): ?>
  <div class="err"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="form-label">Username</label>
      <div class="iw">
        <input type="text" name="username" class="form-control" placeholder="Masukkan username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        <i class="fa-solid fa-user"></i>
      </div>
    </div>
    <div class="form-group" style="margin-bottom:20px">
      <label class="form-label">Password</label>
      <div class="iw">
        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        <i class="fa-solid fa-lock"></i>
      </div>
    </div>
    <button type="submit" class="btn-login">
      <i class="fa-solid fa-right-to-bracket"></i> MASUK
    </button>
  </form>

  <?php if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)): ?>
  <div class="hint-box">
    Default login: <strong>admin</strong> / <strong>password</strong>
    &nbsp;·&nbsp; <strong>kasir1</strong> / <strong>password</strong>
  </div>
  <?php endif; ?>
  <div class="login-foot">v<?= APP_VERSION ?> &copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?></div>
</div>

<script>
var BASE_URL = <?= json_encode(BASE_URL) ?>;
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register(BASE_URL + '/sw.js').catch(function(){});
}
</script>
</body>
</html>
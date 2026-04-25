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
    $fields = ['store_name','store_address','store_phone','receipt_footer','tax_percent','currency','receipt_paper_size',
               'bank_name','bank_account_number','bank_account_holder'];
    foreach ($fields as $f) {
        $val = sanitize($_POST[$f] ?? '');
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$f, $val]);
    }

    // Logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $result = secureImageUpload($_FILES['logo'], 'logo', 'logo');
        if (!$result['success']) {
            $msg = $result['message']; $msgType = 'danger';
        } else {
            $oldLogo = getSetting('store_logo');
            if ($oldLogo && $oldLogo !== 'logo.png' && file_exists(UPLOAD_PATH . 'logo/' . $oldLogo)) {
                unlink(UPLOAD_PATH . 'logo/' . $oldLogo);
            }
            $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='store_logo'")->execute([$result['filename']]);
        }
    }

    // QRIS upload
    if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
        $result = secureImageUpload($_FILES['qris_image'], 'logo', 'qris');
        if (!$result['success']) {
            $msg = $result['message']; $msgType = 'danger';
        } else {
            $oldQris = getSetting('qris_image');
            if ($oldQris && file_exists(UPLOAD_PATH . 'logo/' . $oldQris)) {
                unlink(UPLOAD_PATH . 'logo/' . $oldQris);
            }
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('qris_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$result['filename']]);
        }
    }

    if (empty($msg)) $msg = 'Pengaturan berhasil disimpan!';
    clearSettingsCache();
    } // end csrf check
}

// Load settings
$settingsRaw = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$settings = [];
foreach ($settingsRaw as $s) $settings[$s['setting_key']] = $s['setting_value'];

$pageTitle = 'Pengaturan - Admin';
include '../includes/header.php';
?>
<div class="admin-layout">
<?php include '../includes/sidebar.php'; ?>
<div class="admin-content">
  <div class="page-header">
    <div><div class="page-title">Pengaturan Toko</div></div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> mb-3"><i class="fa-solid fa-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="settingsForm">
    <?= csrfField() ?>
    <div class="grid-2" style="gap:24px">
      <!-- Settings Form -->
      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-store" style="color:var(--yellow)"></i> Informasi Toko</span></div>
        <div class="card-body" style="padding:16px;">
          <div class="form-group">
          <label class="form-label">Nama Toko *</label>
          <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Alamat Toko</label>
          <textarea name="store_address" class="form-control" rows="3" placeholder="Alamat lengkap toko..."><?= htmlspecialchars($settings['store_address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">No. Telepon / WhatsApp</label>
          <input type="text" name="store_phone" class="form-control" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>" placeholder="08xxxxxxxxxx">
        </div>
        <div class="form-group">
          <label class="form-label">Pesan Footer Struk</label>
          <input type="text" name="receipt_footer" class="form-control" value="<?= htmlspecialchars($settings['receipt_footer'] ?? 'Terima kasih telah berbelanja!') ?>">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Simbol Mata Uang</label>
            <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($settings['currency'] ?? 'Rp') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Pajak (%)</label>
            <input type="number" name="tax_percent" class="form-control" value="<?= htmlspecialchars($settings['tax_percent'] ?? '0') ?>" min="0" max="100" step="0.1">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Ukuran Kertas Struk</label>
          <select name="receipt_paper_size" class="form-control">
            <option value="80" <?= ($settings['receipt_paper_size'] ?? '80') === '80' ? 'selected' : '' ?>>80mm (Standar)</option>
            <option value="58" <?= ($settings['receipt_paper_size'] ?? '80') === '58' ? 'selected' : '' ?>>58mm (Kecil)</option>
          </select>
          <div class="form-hint">Sesuaikan dengan ukuran kertas printer thermal Anda</div>
        </div>
        <div class="form-group">
          <label class="form-label">Upload Logo Toko (JPG/PNG, Maks 2MB)</label>
          <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png" id="logoInput">
          <div class="form-hint">Logo akan tampil di halaman kasir, login, dan struk</div>
          <div id="logoPreview" style="margin-top:10px"></div>
        </div>
          <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
        </div>
      </div>

      <!-- Preview + Payment Settings -->
      <div>
        <!-- Payment Settings (Landing Page) -->
        <div class="card mb-3">
          <div class="card-header"><span class="card-title"><i class="fa-solid fa-credit-card" style="color:var(--primary)"></i> Pembayaran Online (Landing Page)</span></div>
          <div class="card-body" style="padding:16px;">

          <div class="form-group">
            <label class="form-label">Upload QRIS Statis (JPG/PNG, Maks 2MB)</label>
            <input type="file" name="qris_image" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">Upload gambar QR Code QRIS statis Anda</div>
            <?php $qrisImg = $settings['qris_image'] ?? ''; if ($qrisImg): ?>
            <div style="margin-top:10px;text-align:center">
              <img src="<?= UPLOAD_URL ?>logo/<?= htmlspecialchars($qrisImg) ?>" alt="QRIS" style="max-height:180px;border-radius:8px;border:2px solid var(--border)">
              <div style="font-size:11px;color:var(--text-muted);margin-top:6px">QRIS saat ini</div>
            </div>
            <?php endif; ?>
          </div>
          <div style="border-top:1px solid var(--border);margin:16px 0;padding-top:16px">
            <label class="form-label" style="font-weight:700;margin-bottom:12px"><i class="fa-solid fa-building-columns"></i> Rekening Bank</label>
          </div>
          <div class="form-group">
            <label class="form-label">Nama Bank</label>
            <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($settings['bank_name'] ?? '') ?>" placeholder="Contoh: BCA, BRI, Mandiri">
          </div>
          <div class="form-group">
            <label class="form-label">Nomor Rekening</label>
            <input type="text" name="bank_account_number" class="form-control" value="<?= htmlspecialchars($settings['bank_account_number'] ?? '') ?>" placeholder="1234567890">
          </div>
          <div class="form-group">
            <label class="form-label">Atas Nama</label>
            <input type="text" name="bank_account_holder" class="form-control" value="<?= htmlspecialchars($settings['bank_account_holder'] ?? '') ?>" placeholder="Nama pemilik rekening">
          </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;"><i class="fa-solid fa-floppy-disk"></i> Simpan Semua Perubahan</button>
          </div>
        </div>

      <div class="card mb-3">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-eye" style="color:var(--info)"></i> Logo Saat Ini</span></div>
        <div style="text-align:center;padding:20px">
          <?php $logoUrl = getLogoUrl(); ?>
          <img src="<?= htmlspecialchars($logoUrl) ?>" id="currentLogo" alt="Logo"
               style="max-height:130px;width:auto;filter:drop-shadow(0 4px 16px rgba(192,57,43,0.3))">
          <div style="margin-top:12px;font-size:13px;color:var(--text-muted)">Logo toko ditampilkan di header, login, dan invoice</div>
        </div>
      </div>

      <!-- Printer Detection -->
      <div class="card mb-3" id="printerCard">
        <div class="card-header">
          <span class="card-title"><i class="fa-solid fa-print" style="color:var(--green)"></i> Deteksi Printer</span>
        </div>
        <div style="padding:16px">
          <!-- Browser capabilities -->
          <div style="margin-bottom:16px">
            <label class="form-label" style="font-weight:700;margin-bottom:10px"><i class="fa-solid fa-circle-info"></i> Kemampuan Browser</label>
            <div id="browserCaps" style="display:flex;flex-direction:column;gap:6px"></div>
          </div>

          <!-- Detected printers -->
          <div style="margin-bottom:16px">
            <label class="form-label" style="font-weight:700;margin-bottom:10px"><i class="fa-solid fa-list-check"></i> Printer Terdeteksi</label>
            <div id="printerList" style="display:flex;flex-direction:column;gap:6px">
              <div style="font-size:13px;color:var(--text-muted);padding:12px;text-align:center;background:var(--dark);border-radius:var(--radius)">
                <i class="fa-solid fa-arrow-pointer" style="margin-right:4px"></i> Klik tombol deteksi di bawah
              </div>
            </div>
          </div>

          <!-- Action buttons -->
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">
            <button type="button" class="btn btn-secondary" id="btnDetectUsb" onclick="detectUsbPrinter()" style="flex:1;min-width:140px">
              <i class="fa-brands fa-usb"></i> Deteksi USB
            </button>
            <button type="button" class="btn btn-secondary" id="btnDetectBt" onclick="detectBluetoothPrinter()" style="flex:1;min-width:140px">
              <i class="fa-brands fa-bluetooth-b"></i> Deteksi Bluetooth
            </button>
            <button type="button" class="btn btn-primary" onclick="testPrint()" style="flex:1;min-width:140px">
              <i class="fa-solid fa-print"></i> Test Print
            </button>
          </div>

          <!-- Connection log -->
          <div>
            <label class="form-label" style="font-weight:700;margin-bottom:8px"><i class="fa-solid fa-terminal"></i> Log</label>
            <div id="printerLog" style="background:var(--dark);border-radius:var(--radius);padding:10px 12px;font-family:'Courier New',monospace;font-size:11px;max-height:160px;overflow-y:auto;line-height:1.6;color:var(--text-muted)">
              <div>Siap mendeteksi printer...</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa-solid fa-file-invoice" style="color:var(--yellow)"></i> Preview Struk Thermal</span></div>
        <div style="background:#fff;border-radius:12px;padding:12px;font-family:'Courier New',Courier,monospace;color:#000;max-width:300px;margin:0 auto;font-size:12px;line-height:1.4">
          <div style="text-align:center">
            <img src="<?= htmlspecialchars($logoUrl) ?>" style="height:36px;width:auto;display:block;margin:0 auto 4px">
            <div style="font-size:14px;font-weight:bold"><?= htmlspecialchars($settings['store_name'] ?? '') ?></div>
            <div style="font-size:10px"><?= htmlspecialchars($settings['store_address'] ?? '') ?></div>
            <?php if (!empty($settings['store_phone'])): ?>
            <div style="font-size:10px">Telp: <?= htmlspecialchars($settings['store_phone']) ?></div>
            <?php endif; ?>
          </div>
          <div style="text-align:center;font-size:10px;letter-spacing:1px">----------------------------------------</div>
          <div style="display:flex;justify-content:space-between;font-size:11px"><span>No</span><span>INV202406010001</span></div>
          <div style="display:flex;justify-content:space-between;font-size:11px"><span>Tgl</span><span><?= date('d/m/Y H:i:s') ?></span></div>
          <div style="display:flex;justify-content:space-between;font-size:11px"><span>Kasir</span><span>Admin</span></div>
          <div style="text-align:center;font-size:10px;letter-spacing:1px">----------------------------------------</div>
          <div style="font-size:11px;margin-top:2px">Nugget Ayam 500g</div>
          <div style="display:flex;justify-content:space-between;font-size:11px;color:#333"><span>2 x Rp 25.000</span><span>Rp 50.000</span></div>
          <div style="font-size:11px;margin-top:2px">Sosis Sapi 1kg</div>
          <div style="display:flex;justify-content:space-between;font-size:11px;color:#333"><span>1 x Rp 45.000</span><span>Rp 45.000</span></div>
          <div style="text-align:center;font-size:10px;letter-spacing:1px">----------------------------------------</div>
          <div style="display:flex;justify-content:space-between;font-size:11px"><span>Subtotal</span><span>Rp 95.000</span></div>
          <div style="display:flex;justify-content:space-between;font-size:14px;font-weight:bold;border-top:1px dashed #000;border-bottom:1px dashed #000;padding:3px 0;margin:4px 0"><span>TOTAL</span><span>Rp 95.000</span></div>
          <div style="display:flex;justify-content:space-between;font-size:11px"><span>Bayar (tunai)</span><span>Rp 100.000</span></div>
          <div style="display:flex;justify-content:space-between;font-size:11px"><span>Kembalian</span><span>Rp 5.000</span></div>
          <div style="text-align:center;font-size:10px;letter-spacing:1px">----------------------------------------</div>
          <div style="text-align:center;font-size:10px;margin-top:4px">
            <?= htmlspecialchars($settings['receipt_footer'] ?? 'Terima kasih telah berbelanja!') ?>
          </div>
        </div>
      </div>
      </div>
    </div>
  </form>
</div>
</div>
<script>
document.getElementById('logoInput').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('logoPreview').innerHTML = `<img src="${e.target.result}" style="height:60px;border-radius:8px;border:2px solid var(--border)">`;
    document.getElementById('currentLogo').src = e.target.result;
  };
  reader.readAsDataURL(file);
});

// =============================================
// PRINTER DETECTION
// =============================================
const printerLog = document.getElementById('printerLog');
const printerList = document.getElementById('printerList');
let detectedPrinters = [];

function pLog(msg, type) {
  const colors = { info: 'var(--info)', success: 'var(--green)', error: 'var(--red)', warn: '#f39c12' };
  const icons = { info: 'ℹ️', success: '✅', error: '❌', warn: '⚠️' };
  const time = new Date().toLocaleTimeString('id-ID');
  const safeMsg = msg.replace(/</g, '&lt;').replace(/>/g, '&gt;');
  printerLog.innerHTML += `<div style="color:${colors[type]||'var(--text-muted)'}"><span style="opacity:.5">[${time}]</span> ${icons[type]||''} ${safeMsg}</div>`;
  printerLog.scrollTop = printerLog.scrollHeight;
}

function addPrinterToList(name, type, detail, deviceObj) {
  detectedPrinters.push({ name, type, detail, device: deviceObj });
  renderPrinterList();
}

function renderPrinterList() {
  if (!detectedPrinters.length) {
    printerList.innerHTML = '<div style="font-size:13px;color:var(--text-muted);padding:12px;text-align:center;background:var(--dark);border-radius:var(--radius)"><i class="fa-solid fa-arrow-pointer" style="margin-right:4px"></i> Klik tombol deteksi di bawah</div>';
    return;
  }
  printerList.innerHTML = detectedPrinters.map((p, i) => {
    const iconMap = { usb: 'fa-brands fa-usb', bluetooth: 'fa-brands fa-bluetooth-b', network: 'fa-solid fa-wifi' };
    const colorMap = { usb: 'var(--info)', bluetooth: '#3498db', network: 'var(--green)' };
    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--dark);border-radius:var(--radius);border-left:3px solid ${colorMap[p.type]||'var(--border)'}">
      <div style="display:flex;align-items:center;gap:10px">
        <i class="${iconMap[p.type]||'fa-solid fa-print'}" style="font-size:18px;color:${colorMap[p.type]||'var(--text-muted)'}"></i>
        <div>
          <div style="font-weight:600;font-size:13px">${escHtml(p.name)}</div>
          <div style="font-size:11px;color:var(--text-muted)">${escHtml(p.detail)}</div>
        </div>
      </div>
      <span style="font-size:10px;padding:3px 8px;border-radius:10px;background:${colorMap[p.type]||'var(--border)'}20;color:${colorMap[p.type]||'var(--text-muted)'};text-transform:uppercase;font-weight:600">${p.type}</span>
    </div>`;
  }).join('');
}

// Check browser capabilities on load
(function checkBrowserCaps() {
  const caps = document.getElementById('browserCaps');
  const checks = [
    { label: 'Window Print', supported: typeof window.print === 'function', desc: 'Cetak via dialog browser' },
    { label: 'WebUSB API', supported: !!navigator.usb, desc: 'Deteksi printer USB' },
    { label: 'Web Bluetooth', supported: !!navigator.bluetooth, desc: 'Deteksi printer Bluetooth' },
    { label: 'Web Serial', supported: !!navigator.serial, desc: 'Koneksi serial/COM port' },
    { label: 'HTTPS / Secure', supported: location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1', desc: 'Diperlukan untuk USB & Bluetooth' }
  ];
  caps.innerHTML = checks.map(c =>
    `<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--dark);border-radius:var(--radius);font-size:12px">
      <i class="fa-solid ${c.supported ? 'fa-circle-check' : 'fa-circle-xmark'}" style="color:${c.supported ? 'var(--green)' : 'var(--red)'};font-size:14px"></i>
      <div style="flex:1">
        <span style="font-weight:600">${c.label}</span>
        <span style="color:var(--text-muted);margin-left:6px">${c.desc}</span>
      </div>
      <span style="font-size:10px;padding:2px 6px;border-radius:8px;background:${c.supported ? 'var(--green)' : 'var(--red)'}15;color:${c.supported ? 'var(--green)' : 'var(--red)'}">${c.supported ? 'Didukung' : 'Tidak'}</span>
    </div>`
  ).join('');

  // Disable buttons if not supported
  if (!navigator.usb) {
    document.getElementById('btnDetectUsb').disabled = true;
    document.getElementById('btnDetectUsb').title = 'Browser tidak mendukung WebUSB';
  }
  if (!navigator.bluetooth) {
    document.getElementById('btnDetectBt').disabled = true;
    document.getElementById('btnDetectBt').title = 'Browser tidak mendukung Web Bluetooth';
  }
})();

// Detect USB Printer
async function detectUsbPrinter() {
  if (!navigator.usb) {
    pLog('Browser tidak mendukung WebUSB. Gunakan Chrome/Edge terbaru via HTTPS.', 'error');
    return;
  }
  pLog('Meminta akses perangkat USB...', 'info');
  try {
    const device = await navigator.usb.requestDevice({
      filters: [
        { classCode: 7 }, // Printer class
      ]
    });
    const name = device.productName || device.serialNumber || 'USB Device';
    const detail = `Vendor: 0x${device.vendorId.toString(16).toUpperCase().padStart(4,'0')} | Product: 0x${device.productId.toString(16).toUpperCase().padStart(4,'0')}`;
    pLog(`Printer USB ditemukan: <b>${escHtml(name)}</b>`, 'success');
    pLog(detail, 'info');
    addPrinterToList(name, 'usb', detail, device);
  } catch (e) {
    if (e.name === 'NotFoundError') {
      pLog('Tidak ada perangkat USB yang dipilih.', 'warn');
    } else if (e.name === 'SecurityError') {
      pLog('Akses USB ditolak. Pastikan menggunakan HTTPS.', 'error');
    } else {
      pLog('Gagal deteksi USB: ' + e.message, 'error');
    }
  }
}

// Detect Bluetooth Printer
async function detectBluetoothPrinter() {
  if (!navigator.bluetooth) {
    pLog('Browser tidak mendukung Web Bluetooth. Gunakan Chrome/Edge terbaru.', 'error');
    return;
  }
  pLog('Mencari printer Bluetooth...', 'info');
  try {
    const device = await navigator.bluetooth.requestDevice({
      acceptAllDevices: true,
      optionalServices: ['generic_access']
    });
    const name = device.name || 'Bluetooth Device';
    const detail = `ID: ${device.id.substring(0, 16)}...`;
    pLog(`Perangkat Bluetooth ditemukan: <b>${escHtml(name)}</b>`, 'success');
    addPrinterToList(name, 'bluetooth', detail, device);

    device.addEventListener('gattserverdisconnected', () => {
      pLog(`${escHtml(name)} terputus.`, 'warn');
    });
  } catch (e) {
    if (e.name === 'NotFoundError') {
      pLog('Tidak ada perangkat Bluetooth yang dipilih.', 'warn');
    } else if (e.name === 'SecurityError') {
      pLog('Akses Bluetooth ditolak. Pastikan menggunakan HTTPS.', 'error');
    } else {
      pLog('Gagal deteksi Bluetooth: ' + e.message, 'error');
    }
  }
}

// Test Print - print a test receipt
function testPrint() {
  pLog('Mengirim test print...', 'info');
  const storeName = <?= json_encode($settings['store_name'] ?? 'Fun Frozen Food') ?>;
  const storeAddr = <?= json_encode($settings['store_address'] ?? '') ?>;
  const storePhone = <?= json_encode($settings['store_phone'] ?? '') ?>;
  const paperSize = <?= json_encode($settings['receipt_paper_size'] ?? '80') ?>;
  const footer = <?= json_encode($settings['receipt_footer'] ?? 'Terima kasih telah berbelanja!') ?>;
  const now = new Date().toLocaleString('id-ID');

  const testHtml = `
    <div id="printArea" data-paper="${paperSize}">
      <div class="r-center">
        <div class="r-store">${escHtml(storeName)}</div>
        ${storeAddr ? '<div class="r-addr">' + escHtml(storeAddr) + '</div>' : ''}
        ${storePhone ? '<div class="r-addr">Telp: ' + escHtml(storePhone) + '</div>' : ''}
      </div>
      <div class="r-center" style="font-size:10px;letter-spacing:1px">${paperSize==='58' ? '--------------------------------' : '----------------------------------------'}</div>
      <div class="r-center" style="font-size:14px;font-weight:bold;padding:8px 0">🖨️ TEST PRINT</div>
      <div class="r-center" style="font-size:10px;letter-spacing:1px">${paperSize==='58' ? '--------------------------------' : '----------------------------------------'}</div>
      <div class="r-row"><span>Waktu</span><span>${escHtml(now)}</span></div>
      <div class="r-row"><span>Kertas</span><span>${paperSize}mm</span></div>
      <div class="r-row"><span>Browser</span><span>${escHtml(navigator.userAgent.split(') ').pop().split('/')[0] || 'Unknown')}</span></div>
      <div class="r-center" style="font-size:10px;letter-spacing:1px">${paperSize==='58' ? '--------------------------------' : '----------------------------------------'}</div>
      <div class="r-item-name">Nugget Ayam 500g</div>
      <div class="r-item-detail"><span>2 x Rp 25.000</span><span>Rp 50.000</span></div>
      <div class="r-item-name">Sosis Sapi 1kg</div>
      <div class="r-item-detail"><span>1 x Rp 45.000</span><span>Rp 45.000</span></div>
      <div class="r-center" style="font-size:10px;letter-spacing:1px">${paperSize==='58' ? '--------------------------------' : '----------------------------------------'}</div>
      <div class="r-total"><span>TOTAL</span><span>Rp 95.000</span></div>
      <div class="r-center" style="font-size:10px;letter-spacing:1px">${paperSize==='58' ? '--------------------------------' : '----------------------------------------'}</div>
      <div class="r-footer">${escHtml(footer)}</div>
      <div class="r-center" style="font-size:9px;margin-top:4px;opacity:.6">— Test print selesai —</div>
    </div>`;

  if (typeof printReceipt === 'function') {
    printReceipt(testHtml, paperSize);
    pLog('Dialog print dibuka. Pilih printer Anda dari daftar.', 'success');
  } else {
    pLog('Fungsi printReceipt belum dimuat.', 'error');
  }
}
</script>
<?php include '../includes/footer.php'; ?>

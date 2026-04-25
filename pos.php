<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'Kasir - ' . (getSetting('store_name') ?: 'Fun Frozen Food');
include 'includes/header.php';
?>

<div class="pos-layout">

  <!-- LEFT: PRODUCTS -->
  <div class="pos-products">
    <!-- Search + Category (sticky header) -->
    <div class="pos-sticky-header">
      <div class="pos-search-bar">
        <div class="search-wrap" style="flex:1">
          <i class="fa-solid fa-search"></i>
          <input type="text" id="searchInput" class="search-input" placeholder="Cari produk...">
        </div>
        <a href="<?= BASE_URL ?>/kasir_produk.php" class="btn btn-secondary btn-sm" title="Kelola Produk" style="white-space:nowrap">
          <i class="fa-solid fa-box"></i> Kelola Produk
        </a>
        <button class="btn btn-secondary btn-sm" onclick="POS.openHistory()" title="Riwayat Transaksi" style="white-space:nowrap">
          <i class="fa-solid fa-clock-rotate-left"></i> Riwayat
        </button>
      </div>
      <!-- Category Filter -->
      <div class="category-filter" id="categoryFilter">
        <button class="cat-btn active" data-id="0">Semua</button>
      </div>
    </div>
    <!-- Product Grid -->
    <div class="product-grid" id="productGrid">
      <div style="grid-column:1/-1;text-align:center;padding:40px"><div class="spinner"></div></div>
    </div>
  </div>

  <!-- RIGHT: CART -->
  <div class="pos-cart">
    <div class="cart-header">
      <div class="cart-title">
        <i class="fa-solid fa-cart-shopping" style="color:var(--red)"></i>
        Keranjang
        <span class="cart-count" id="cartCount">0</span>
      </div>
      <button class="btn btn-sm btn-secondary" onclick="POS.clearCart()">
        <i class="fa-solid fa-trash-can"></i> Reset
      </button>
    </div>
    <div class="cart-items" id="cartItems">
      <div class="cart-empty">
        <i class="fa-solid fa-cart-shopping"></i>
        <p>Keranjang kosong</p>
        <small style="font-size:12px;opacity:0.6">Klik produk untuk menambahkan</small>
      </div>
    </div>
    <div class="cart-footer">
      <div class="cart-total-row">
        <span class="cart-total-label">Total Belanja</span>
        <span class="cart-total-value" id="cartTotal">Rp 0</span>
      </div>
      <button class="btn-checkout" id="btnCheckout" onclick="POS.openCheckout()" disabled>
        <i class="fa-solid fa-credit-card"></i> CHECKOUT
      </button>
    </div>
  </div>
</div>

<!-- MODAL: CHECKOUT -->
<div class="modal-overlay" id="checkoutModal" style="display:none">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title"><i class="fa-solid fa-receipt" style="color:var(--yellow)"></i> Checkout</span>
      <button class="modal-close" onclick="closeModal('checkoutModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Nama Customer (Opsional)</label>
        <input type="text" id="customerName" class="form-control" placeholder="Nama pelanggan...">
      </div>
      <div class="form-group">
        <label class="form-label">Metode Pembayaran</label>
        <select id="paymentMethod" class="form-select" onchange="POS.onPaymentChange()">
          <option value="tunai">💵 Tunai</option>
          <option value="transfer">📱 Transfer</option>
        </select>
      </div>
      <div id="paidGroup" class="form-group">
        <label class="form-label">Uang Bayar</label>
        <div class="input-group">
          <span class="input-prefix">Rp</span>
          <input type="number" id="paidAmount" class="form-control" placeholder="0" oninput="POS.calcChange()" min="0">
        </div>
        <!-- Quick amount buttons -->
        <div id="quickAmounts" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px"></div>
      </div>
      <div style="background:var(--dark);border-radius:var(--radius);padding:16px;margin-bottom:16px">
        <div id="checkoutSubtotalRow" style="display:none;justify-content:space-between;margin-bottom:8px;font-size:14px">
          <span class="text-muted">Subtotal</span>
          <span id="checkoutSubtotal">Rp 0</span>
        </div>
        <div id="checkoutTaxRow" style="display:none;justify-content:space-between;margin-bottom:8px;font-size:14px">
          <span class="text-muted" id="checkoutTaxLabel">Pajak (0%)</span>
          <span id="checkoutTax">Rp 0</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px">
          <span class="text-muted">Total Belanja</span>
          <span class="fw-bolder" id="checkoutTotal">Rp 0</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:14px">
          <span class="text-muted">Kembalian</span>
          <span class="fw-bolder text-yellow" id="changeAmount">Rp 0</span>
        </div>
      </div>
      <button class="btn btn-primary btn-block btn-lg" id="btnPay" onclick="POS.submitCheckout()">
        <i class="fa-solid fa-check-circle"></i> BAYAR SEKARANG
      </button>
    </div>
  </div>
</div>

<!-- MODAL: INVOICE -->
<div class="modal-overlay" id="invoiceModal" style="display:none">
  <div class="modal-box" style="max-width:440px">
    <div class="modal-header no-print">
      <span class="modal-title"><i class="fa-solid fa-file-invoice" style="color:var(--yellow)"></i> Struk / Invoice</span>
      <button class="modal-close" onclick="closeModal('invoiceModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div id="invoiceContent"></div>
      <div class="d-flex gap-2 mt-3 no-print" id="invoiceActions">
        <!-- Mobile: Share to printer app (primary) -->
        <button class="btn btn-success flex-1 mobile-only-btn" id="btnSharePdf" onclick="POS.shareInvoicePdf()" style="display:none">
          <i class="fa-solid fa-share-nodes"></i> Kirim ke Printer
        </button>
        <!-- Download PDF (works everywhere) -->
        <button class="btn btn-success flex-1" id="btnDownloadPdf" onclick="POS.downloadInvoicePdf()">
          <i class="fa-solid fa-download"></i> Download PDF
        </button>
        <!-- Desktop: Direct print -->
        <button class="btn btn-primary flex-1 desktop-only-btn" onclick="POS.printInvoice()">
          <i class="fa-solid fa-print"></i> Cetak
        </button>
        <button class="btn btn-secondary" onclick="closeModal('invoiceModal')" style="padding:0 14px">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="print-guide no-print" id="printGuide">
        <div class="print-guide-toggle" onclick="this.parentElement.classList.toggle('open')">
          <i class="fa-solid fa-circle-question"></i> Cara cetak struk dari HP
          <i class="fa-solid fa-chevron-down print-guide-arrow"></i>
        </div>
        <div class="print-guide-body">
          <p><strong><i class="fa-solid fa-star" style="color:#2ecc71"></i> Cara Tercepat (Recommended):</strong></p>
          <ol>
            <li>Tekan <b>Kirim ke Printer</b> (tombol hijau)</li>
            <li>Pilih aplikasi printer (misal: <b>RawBT</b>, atau <b>Print</b>)</li>
            <li>Struk langsung tercetak! Ukuran kertas <?= ($settings['receipt_paper_size'] ?? '80') ?>mm otomatis</li>
          </ol>
          <p style="margin-top:8px"><strong><i class="fa-solid fa-download"></i> Alternatif via Download PDF:</strong></p>
          <ol>
            <li>Tekan <b>Download PDF</b></li>
            <li>Buka file PDF yang ter-download</li>
            <li>Tekan <b>Share → Print</b> atau buka langsung dari notifikasi</li>
            <li>Pilih printer thermal kamu</li>
          </ol>
          <p style="margin-top:8px"><strong><i class="fa-solid fa-mobile-screen"></i> Tips Printer Bluetooth:</strong></p>
          <ol>
            <li>Install app <b>RawBT</b> (gratis di Play Store) untuk Android</li>
            <li>Pair/hubungkan printer Bluetooth di Settings HP</li>
            <li>Setelah itu, printer muncul otomatis di menu Share/Print</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: TRANSACTION HISTORY (Kasir Reprint) -->
<div class="modal-overlay" id="historyModal" style="display:none">
  <div class="modal-box" style="max-width:600px">
    <div class="modal-header">
      <span class="modal-title"><i class="fa-solid fa-clock-rotate-left" style="color:var(--yellow)"></i> Riwayat Transaksi</span>
      <button class="modal-close" onclick="closeModal('historyModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="max-height:70vh;overflow-y:auto">
      <div id="historyList"><div style="text-align:center;padding:30px"><div class="spinner"></div></div></div>
    </div>
  </div>
</div>

<!-- MODAL: HISTORY DETAIL (Reprint) -->
<div class="modal-overlay" id="historyDetailModal" style="display:none">
  <div class="modal-box" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title"><i class="fa-solid fa-receipt" style="color:var(--yellow)"></i> Detail Transaksi</span>
      <button class="modal-close" onclick="closeModal('historyDetailModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div id="historyDetailContent"></div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary flex-1" onclick="POS.reprintInvoice()">
          <i class="fa-solid fa-print"></i> Cetak Ulang
        </button>
        <button class="btn btn-success flex-1" onclick="POS.downloadHistoryPdf()">
          <i class="fa-solid fa-download"></i> PDF
        </button>
        <button class="btn btn-secondary" onclick="closeModal('historyDetailModal')" style="padding:0 14px">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Floating Cart Button (mobile) -->
<button class="pos-cart-fab" id="cartFab" onclick="toggleMobileCart()">
  <i class="fa-solid fa-cart-shopping"></i>
  <span class="pos-cart-fab-count" id="cartFabCount">0</span>
</button>
<div class="pos-cart-backdrop" id="cartBackdrop" onclick="toggleMobileCart()"></div>

<?php $hideDesktopSidebar = true; include 'includes/sidebar.php'; ?>

<script>
function toggleMobileCart() {
  var cart = document.querySelector('.pos-cart');
  var backdrop = document.getElementById('cartBackdrop');
  var fab = document.getElementById('cartFab');
  if (!cart) return;
  if (cart.classList.contains('show')) {
    cart.classList.remove('show');
    backdrop.classList.remove('show');
    fab.classList.remove('hide');
  } else {
    cart.classList.add('show');
    backdrop.classList.add('show');
    fab.classList.add('hide');
  }
}
// Allow closing cart by clicking the drag handle area
document.addEventListener('DOMContentLoaded', function() {
  var cartHeader = document.querySelector('.pos-cart .cart-header');
  if (cartHeader) {
    cartHeader.addEventListener('click', function(e) {
      if (window.innerWidth <= 768) {
        toggleMobileCart();
      }
    });
  }
});
</script>

<?php include 'includes/footer.php'; ?>

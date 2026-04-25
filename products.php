<?php
require_once __DIR__ . '/config/config.php';
$storeName    = getSetting('store_name') ?: 'Fun Frozen Food';
$storePhone   = getSetting('store_phone') ?: '';
$waPhone      = preg_replace('/[^0-9]/', '', $storePhone);
if (strpos($waPhone, '0') === 0) {
    $waPhone = '62' . substr($waPhone, 1);
}
$logoUrl      = getLogoUrl();
$qrisImage    = getSetting('qris_image');
$qrisUrl      = $qrisImage ? (UPLOAD_URL . 'logo/' . $qrisImage) : '';
$bankName     = getSetting('bank_name');
$bankNumber   = getSetting('bank_account_number');
$bankHolder   = getSetting('bank_account_holder');
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Katalog Produk — <?= htmlspecialchars($storeName) ?></title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<meta name="theme-color" content="#c0392b">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@300;400;500;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
tailwind.config={darkMode:"class",theme:{extend:{colors:{"surface-variant":"#d9dedb","inverse-primary":"#ffa4a4","on-secondary":"#fff0e6","on-secondary-fixed":"#522a00","surface-container-low":"#fdf2f0","surface-container-lowest":"#ffffff","surface-container-highest":"#f0ddd9","secondary-fixed":"#ffc698","outline":"#747776","on-tertiary-container":"#601500","primary-fixed-dim":"#ff8a8a","surface-dim":"#e8d5d1","inverse-surface":"#1a0e0b","tertiary-dim":"#962600","on-error":"#ffefec","on-background":"#2c2222","surface-container-high":"#f5e3df","background":"#fef7f5","surface-tint":"#c0392b","secondary":"#8b4b00","inverse-on-surface":"#9b9d9c","on-primary-fixed-variant":"#a02d20","tertiary-fixed":"#ff9475","on-secondary-container":"#6e3a00","outline-variant":"#d4a8a0","error-dim":"#b92902","surface-container":"#f8e8e5","on-secondary-fixed-variant":"#7b4200","on-tertiary-fixed-variant":"#6f1a00","secondary-dim":"#7a4100","tertiary-fixed-dim":"#ff7d57","primary-fixed":"#ffb4b0","on-surface":"#2c2222","error-container":"#f95630","error":"#b02500","primary-dim":"#a02d20","tertiary":"#ab2d00","tertiary-container":"#ff9475","surface-bright":"#fef7f5","on-tertiary":"#ffefec","on-tertiary-fixed":"#340700","on-primary-fixed":"#5c0f08","on-primary-container":"#8b2218","surface":"#fef7f5","secondary-fixed-dim":"#ffb472","secondary-container":"#ffc698","on-surface-variant":"#6b5552","on-error-container":"#520c00","primary":"#c0392b","primary-container":"#ffb4b0","on-primary":"#ffffff"},fontFamily:{"headline":["Plus Jakarta Sans"],"body":["Be Vietnam Pro"],"label":["Be Vietnam Pro"]},borderRadius:{"DEFAULT":"1rem","lg":"2rem","xl":"3rem","full":"9999px"}}}};
</script>
<style>
  .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
  html{scroll-behavior:smooth}
  .line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
</style>
</head>
<body class="bg-background text-on-surface font-body selection:bg-primary-container selection:text-on-primary-container">

<!-- TopNavBar -->
<header class="fixed top-0 w-full z-50 bg-white/70 backdrop-blur-md shadow-[0_12px_40px_rgba(192,57,43,0.06)] font-headline tracking-tight">
<nav class="flex justify-between items-center px-6 md:px-8 py-4 max-w-screen-2xl mx-auto h-20">
  <div class="flex items-center gap-3">
    <a href="<?= BASE_URL ?>/" class="flex items-center gap-3">
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="h-10 w-auto">
      <span class="text-2xl font-bold tracking-tighter text-red-900"><?= htmlspecialchars($storeName) ?></span>
    </a>
  </div>
  <div class="hidden md:flex items-center space-x-10">
    <a class="text-zinc-600 hover:text-red-800 transition-transform duration-300 hover:scale-105" href="<?= BASE_URL ?>/">Home</a>
    <a class="text-red-800 font-bold" href="<?= BASE_URL ?>/products.php">Katalog</a>
    <a class="text-zinc-600 hover:text-red-800 transition-transform duration-300 hover:scale-105" href="<?= BASE_URL ?>/#about">Tentang Kami</a>
    <a class="text-zinc-600 hover:text-red-800 transition-transform duration-300 hover:scale-105" href="<?= BASE_URL ?>/#contact">Kontak</a>
  </div>
  <div class="flex items-center space-x-4">
    <button onclick="openCartModal()" class="relative p-2 hover:bg-surface-container rounded-full transition-colors">
      <span class="material-symbols-outlined text-red-800">shopping_cart</span>
      <span id="cartBadge" class="hidden absolute -top-1 -right-1 bg-primary text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center">0</span>
    </button>
  </div>
</nav>
</header>

<main class="pt-20">

<!-- Page Header -->
<section class="bg-gradient-to-br from-primary via-red-700 to-red-900 py-16 md:py-20 px-6 md:px-8 relative overflow-hidden">
  <div class="absolute inset-0 opacity-10">
    <div class="absolute top-10 left-10 w-72 h-72 bg-white rounded-full blur-3xl"></div>
    <div class="absolute bottom-10 right-10 w-96 h-96 bg-yellow-300 rounded-full blur-3xl"></div>
  </div>
  <div class="max-w-screen-2xl mx-auto relative z-10">
    <div class="flex items-center gap-2 text-white/70 text-sm mb-4">
      <a href="<?= BASE_URL ?>/" class="hover:text-white transition-colors">Home</a>
      <span class="material-symbols-outlined text-sm">chevron_right</span>
      <span class="text-white font-bold">Katalog Produk</span>
    </div>
    <h1 class="text-4xl md:text-6xl font-headline font-extrabold text-white tracking-tighter">Katalog Produk</h1>
    <p class="text-white/70 mt-3 text-lg max-w-xl">Temukan frozen food berkualitas premium untuk keluarga Anda. Pilih, pesan, dan nikmati!</p>
  </div>
</section>

<!-- Filters & Search -->
<section class="sticky top-20 z-40 bg-white/80 backdrop-blur-lg border-b border-zinc-100 shadow-sm">
  <div class="max-w-screen-2xl mx-auto px-6 md:px-8 py-4">
    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
      <!-- Search -->
      <div class="relative w-full md:w-96">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400 text-xl">search</span>
        <input type="text" id="searchInput" placeholder="Cari produk..." class="w-full pl-12 pr-4 py-3 border border-zinc-200 rounded-full text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none bg-white">
        <button id="clearSearch" class="hidden absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:bg-zinc-100 rounded-full">
          <span class="material-symbols-outlined text-zinc-400 text-lg">close</span>
        </button>
      </div>
      <!-- Category Chips -->
      <div class="flex gap-2 overflow-x-auto pb-1 w-full md:w-auto scrollbar-hide" id="categoryChips">
        <button class="category-chip active shrink-0 px-5 py-2.5 rounded-full text-sm font-bold transition-all" data-cat="all">Semua</button>
      </div>
    </div>
    <!-- Result count -->
    <div class="mt-3 flex items-center justify-between">
      <p id="resultCount" class="text-sm text-zinc-500"></p>
      <!-- Sort -->
      <select id="sortSelect" class="text-sm border border-zinc-200 rounded-full px-4 py-2 bg-white focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none cursor-pointer">
        <option value="name-asc">Nama A-Z</option>
        <option value="name-desc">Nama Z-A</option>
        <option value="price-asc">Harga Terendah</option>
        <option value="price-desc">Harga Tertinggi</option>
      </select>
    </div>
  </div>
</section>

<!-- Product Grid -->
<section class="py-10 md:py-16 px-6 md:px-8">
  <div class="max-w-screen-2xl mx-auto">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6" id="productGrid">
      <div class="col-span-full text-center py-16 text-on-surface-variant">
        <span class="material-symbols-outlined text-5xl animate-spin">progress_activity</span>
        <p class="mt-3">Memuat produk...</p>
      </div>
    </div>
    <!-- Empty state -->
    <div id="emptyState" class="hidden text-center py-20">
      <span class="material-symbols-outlined text-6xl text-zinc-300" style="font-variation-settings:'FILL' 1">search_off</span>
      <h3 class="text-xl font-headline font-bold text-zinc-400 mt-4">Produk tidak ditemukan</h3>
      <p class="text-zinc-400 mt-2 text-sm">Coba kata kunci lain atau pilih kategori yang berbeda</p>
      <button onclick="resetFilters()" class="mt-6 px-6 py-3 bg-primary text-white rounded-full font-bold text-sm hover:bg-primary-dim transition-colors inline-flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">refresh</span> Reset Filter
      </button>
    </div>
  </div>
</section>

</main>

<!-- Footer -->
<footer class="w-full py-10 bg-zinc-100 border-t border-zinc-200">
  <div class="max-w-screen-2xl mx-auto px-6 md:px-8 flex flex-col md:flex-row justify-between items-center gap-4 font-label text-xs uppercase tracking-widest text-red-900">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?>. All rights reserved.</p>
    <div class="flex gap-8">
      <a class="text-zinc-400 hover:text-red-600 transition-colors" href="<?= BASE_URL ?>/">Home</a>
      <a class="text-zinc-400 hover:text-red-600 transition-colors" href="<?= BASE_URL ?>/#about">Tentang</a>
      <a class="text-zinc-400 hover:text-red-600 transition-colors" href="<?= BASE_URL ?>/#contact">Kontak</a>
    </div>
  </div>
</footer>

<!-- Mobile Bottom Navigation -->
<nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-lg flex justify-around items-center py-3 px-6 z-50 border-t border-zinc-100">
  <a href="<?= BASE_URL ?>/" class="flex flex-col items-center gap-1 text-zinc-400">
    <span class="material-symbols-outlined">home</span>
    <span class="text-[10px] font-bold">Home</span>
  </a>
  <a href="<?= BASE_URL ?>/products.php" class="flex flex-col items-center gap-1 text-red-800">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">storefront</span>
    <span class="text-[10px] font-bold">Katalog</span>
  </a>
  <button onclick="openCartModal()" class="flex flex-col items-center gap-1 text-zinc-400 relative">
    <span class="material-symbols-outlined">shopping_cart</span>
    <span class="text-[10px] font-bold">Keranjang</span>
    <span id="cartBadgeMobile" class="hidden absolute -top-1 right-1 bg-primary text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
  </button>
  <a href="<?= BASE_URL ?>/#about" class="flex flex-col items-center gap-1 text-zinc-400">
    <span class="material-symbols-outlined">info</span>
    <span class="text-[10px] font-bold">Info</span>
  </a>
  <a href="https://wa.me/<?= $waPhone ?>" target="_blank" class="flex flex-col items-center gap-1 text-zinc-400">
    <span class="material-symbols-outlined">chat</span>
    <span class="text-[10px] font-bold">Chat</span>
  </a>
</nav>

<!-- ==================== MODALS ==================== -->

<!-- Product Detail Modal -->
<div id="productModal" class="fixed inset-0 z-[100] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('productModal')"></div>
  <div class="absolute inset-4 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
    <div class="relative">
      <div class="bg-surface-container-low h-64 flex items-center justify-center p-6">
        <img id="pmImage" alt="" class="max-h-full max-w-full object-contain drop-shadow-xl">
      </div>
      <button onclick="closeModal('productModal')" class="absolute top-3 right-3 bg-white/80 backdrop-blur rounded-full p-2 hover:bg-white transition-colors shadow">
        <span class="material-symbols-outlined text-zinc-600">close</span>
      </button>
    </div>
    <div class="p-6 overflow-y-auto flex-1">
      <span id="pmCategory" class="text-[10px] font-bold text-secondary-dim uppercase tracking-widest bg-secondary-container/30 px-2 py-0.5 rounded"></span>
      <h3 id="pmName" class="text-2xl font-headline font-bold text-red-900 mt-2"></h3>
      <p id="pmPrice" class="text-2xl font-black text-primary mt-1"></p>
      <p id="pmDesc" class="text-on-surface-variant mt-3 text-sm leading-relaxed"></p>
      <p id="pmStock" class="text-xs text-on-surface-variant mt-2"></p>
      <div class="flex items-center gap-3 mt-6">
        <div class="flex items-center border border-zinc-200 rounded-full overflow-hidden">
          <button onclick="changeQty(-1)" class="px-3 py-2 hover:bg-zinc-100 transition-colors"><span class="material-symbols-outlined text-sm">remove</span></button>
          <span id="pmQty" class="px-4 py-2 font-bold text-center min-w-[40px]">1</span>
          <button onclick="changeQty(1)" class="px-3 py-2 hover:bg-zinc-100 transition-colors"><span class="material-symbols-outlined text-sm">add</span></button>
        </div>
        <button onclick="addToCartFromModal()" class="flex-1 bg-primary text-on-primary py-3 rounded-full font-bold text-sm hover:bg-primary-dim transition-colors inline-flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-base">add_shopping_cart</span> Tambah
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Cart Modal -->
<div id="cartModal" class="fixed inset-0 z-[100] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('cartModal')"></div>
  <div class="absolute inset-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-md bg-white md:rounded-2xl shadow-2xl flex flex-col max-h-[100vh] md:max-h-[90vh]">
    <div class="flex items-center justify-between p-5 border-b border-zinc-100">
      <h3 class="text-xl font-headline font-bold text-red-900"><span class="material-symbols-outlined align-middle mr-1">shopping_cart</span> Keranjang</h3>
      <button onclick="closeModal('cartModal')" class="p-1 hover:bg-zinc-100 rounded-full"><span class="material-symbols-outlined text-zinc-500">close</span></button>
    </div>
    <div id="cartItems" class="flex-1 overflow-y-auto p-5 space-y-4">
      <p class="text-center text-on-surface-variant py-8">Keranjang kosong</p>
    </div>
    <div id="cartFooter" class="hidden border-t border-zinc-100 p-5 space-y-4">
      <div class="flex justify-between items-center">
        <span class="font-bold text-red-900">Total</span>
        <span id="cartTotal" class="text-xl font-black text-primary">Rp 0</span>
      </div>
      <button onclick="openCheckoutModal()" class="w-full bg-primary text-on-primary py-4 rounded-full font-bold text-base hover:bg-primary-dim transition-colors inline-flex items-center justify-center gap-2">
        <span class="material-symbols-outlined">payments</span> Checkout
      </button>
    </div>
  </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 z-[110] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('checkoutModal')"></div>
  <div class="absolute inset-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-lg bg-white md:rounded-2xl shadow-2xl flex flex-col max-h-[100vh] md:max-h-[90vh]">
    <div class="flex items-center justify-between p-5 border-b border-zinc-100">
      <h3 class="text-xl font-headline font-bold text-red-900"><span class="material-symbols-outlined align-middle mr-1">payments</span> Pembayaran</h3>
      <button onclick="closeModal('checkoutModal')" class="p-1 hover:bg-zinc-100 rounded-full"><span class="material-symbols-outlined text-zinc-500">close</span></button>
    </div>
    <div class="flex-1 overflow-y-auto p-5 space-y-6">
      <div>
        <h4 class="font-bold text-sm text-zinc-500 uppercase tracking-wider mb-3">Ringkasan Pesanan</h4>
        <div id="checkoutItems" class="space-y-2 text-sm"></div>
        <div class="flex justify-between items-center mt-3 pt-3 border-t border-zinc-100">
          <span class="font-bold">Total Bayar</span>
          <span id="checkoutTotal" class="text-xl font-black text-primary">Rp 0</span>
        </div>
      </div>
      <div>
        <h4 class="font-bold text-sm text-zinc-500 uppercase tracking-wider mb-3">Data Pemesan</h4>
        <div class="space-y-3">
          <input type="text" id="custName" placeholder="Nama lengkap *" class="w-full border border-zinc-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <input type="text" id="custPhone" placeholder="No. WhatsApp * (08xxx)" class="w-full border border-zinc-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <textarea id="custAddress" rows="2" placeholder="Alamat pengiriman *" class="w-full border border-zinc-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-none"></textarea>
        </div>
      </div>
      <div>
        <h4 class="font-bold text-sm text-zinc-500 uppercase tracking-wider mb-3">Metode Pembayaran</h4>
        <div class="space-y-3">
          <?php if ($qrisUrl): ?>
          <label class="flex items-center gap-3 border border-zinc-200 rounded-xl p-4 cursor-pointer hover:border-primary/40 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary-container/20">
            <input type="radio" name="payMethod" value="qris" class="accent-primary" onchange="togglePayMethod()" checked>
            <span class="material-symbols-outlined text-primary">qr_code_2</span>
            <span class="font-bold text-sm">QRIS</span>
          </label>
          <?php endif; ?>
          <?php if ($bankName && $bankNumber): ?>
          <label class="flex items-center gap-3 border border-zinc-200 rounded-xl p-4 cursor-pointer hover:border-primary/40 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary-container/20">
            <input type="radio" name="payMethod" value="bank" <?= !$qrisUrl ? 'checked' : '' ?> onchange="togglePayMethod()" class="accent-primary">
            <span class="material-symbols-outlined text-primary">account_balance</span>
            <span class="font-bold text-sm">Transfer Bank <?= htmlspecialchars($bankName) ?></span>
          </label>
          <?php endif; ?>
          <label class="flex items-center gap-3 border border-zinc-200 rounded-xl p-4 cursor-pointer hover:border-primary/40 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary-container/20">
            <input type="radio" name="payMethod" value="cod" <?= (!$qrisUrl && !($bankName && $bankNumber)) ? 'checked' : '' ?> onchange="togglePayMethod()" class="accent-primary">
            <span class="material-symbols-outlined text-primary">local_shipping</span>
            <span class="font-bold text-sm">Bayar di Tempat (COD)</span>
          </label>
        </div>
        <?php if ($qrisUrl): ?>
        <div id="qrisDetail" class="mt-4 text-center bg-zinc-50 rounded-xl p-6">
          <p class="text-sm font-bold text-zinc-600 mb-3">Scan QR Code di bawah ini:</p>
          <img src="<?= htmlspecialchars($qrisUrl) ?>" alt="QRIS" class="mx-auto max-h-64 rounded-lg shadow-md">
          <p class="text-xs text-zinc-400 mt-3">Scan menggunakan aplikasi e-wallet atau mobile banking Anda</p>
        </div>
        <?php endif; ?>
        <!-- Bank Detail -->
        <?php if ($bankName && $bankNumber): ?>
        <div id="bankDetail" class="mt-4 bg-zinc-50 rounded-xl p-5 hidden">
          <p class="text-sm font-bold text-zinc-600 mb-3">Transfer ke rekening:</p>
          <div class="bg-white rounded-lg p-4 space-y-2">
            <div class="flex justify-between text-sm"><span class="text-zinc-500">Bank</span><span class="font-bold"><?= htmlspecialchars($bankName) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-zinc-500">No. Rekening</span>
              <span class="font-bold font-mono"><?= htmlspecialchars($bankNumber) ?>
                <button onclick="copyText('<?= htmlspecialchars($bankNumber) ?>')" class="ml-1 text-primary hover:text-primary-dim"><span class="material-symbols-outlined text-sm align-middle">content_copy</span></button>
              </span>
            </div>
            <div class="flex justify-between text-sm"><span class="text-zinc-500">Atas Nama</span><span class="font-bold"><?= htmlspecialchars($bankHolder) ?></span></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- COD Detail -->
        <div id="codDetail" class="hidden mt-4 text-center bg-blue-50 rounded-xl p-6 border border-blue-100">
          <span class="material-symbols-outlined text-blue-500 text-4xl mb-2">local_shipping</span>
          <p class="text-sm font-bold text-blue-800">Bayar saat pesanan tiba</p>
          <p class="text-xs text-blue-600 mt-2">Silakan siapkan uang pas sesuai Total Bayar untuk diberikan ke kurir kami.</p>
        </div>
      </div>
      <div id="proofSection" class="hidden">
        <h4 class="font-bold text-sm text-zinc-500 uppercase tracking-wider mb-3">Upload Bukti Pembayaran</h4>
        <div id="proofUploadArea" class="border-2 border-dashed border-zinc-300 rounded-xl p-6 text-center cursor-pointer hover:border-primary/50 hover:bg-primary-container/5 transition-colors" onclick="document.getElementById('proofInput').click()">
          <input type="file" id="proofInput" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="handleProofUpload(this)">
          <span class="material-symbols-outlined text-4xl text-zinc-400">cloud_upload</span>
          <p class="text-sm text-zinc-500 mt-2">Tap untuk upload bukti transfer</p>
          <p class="text-xs text-zinc-400 mt-1">JPG, PNG maks 2MB</p>
        </div>
        <div id="proofPreview" class="hidden mt-4">
          <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-4">
            <img id="proofThumb" src="" alt="Bukti" class="w-20 h-20 object-cover rounded-lg shadow">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-green-800"><span class="material-symbols-outlined text-sm align-middle">check_circle</span> Bukti berhasil diupload</p>
              <p id="proofFilename" class="text-xs text-green-600 truncate mt-1"></p>
            </div>
            <button onclick="removeProof()" class="text-zinc-400 hover:text-red-500 p-1"><span class="material-symbols-outlined">delete</span></button>
          </div>
        </div>
        <div id="proofUploading" class="hidden mt-4 text-center py-4">
          <span class="material-symbols-outlined text-3xl text-primary animate-spin">progress_activity</span>
          <p class="text-sm text-zinc-500 mt-2">Mengupload bukti...</p>
        </div>
      </div>
    </div>
    <div class="border-t border-zinc-100 p-5 space-y-3">
      <button onclick="submitOrder()" id="btnSubmitOrder" class="w-full bg-green-600 text-white py-4 rounded-full font-bold text-base hover:bg-green-700 transition-colors inline-flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-base">send</span> Kirim Pesanan
      </button>
    </div>
  </div>
</div>

<!-- Order Success Modal -->
<div id="successModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)">
  <div class="bg-white rounded-2xl max-w-sm w-full p-8 text-center shadow-2xl">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <span class="material-symbols-outlined text-green-600" style="font-size:48px">check_circle</span>
    </div>
    <h3 class="text-xl font-bold text-zinc-800 mb-2">Pesanan Berhasil!</h3>
    <p class="text-zinc-500 text-sm mb-3">Pesanan kamu sudah kami terima dan sedang diproses.</p>
    <div class="bg-zinc-50 rounded-xl p-4 mb-4">
      <div class="text-xs text-zinc-400 mb-1">No. Order</div>
      <div id="successOrderNo" class="text-lg font-bold text-zinc-800 font-mono tracking-wider">-</div>
      <div class="text-xs text-zinc-400 mt-2">Total</div>
      <div id="successTotal" class="text-lg font-bold text-green-600">-</div>
    </div>
    <p class="text-xs text-zinc-400 mb-4">Konfirmasi pesanan kamu ke admin via WhatsApp agar segera diproses.</p>
    <a id="successWaBtn" href="#" target="_blank" class="w-full bg-green-500 text-white py-3 rounded-full font-bold text-sm hover:bg-green-600 transition-colors inline-flex items-center justify-center gap-2 mb-3">
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Konfirmasi via WhatsApp
    </a>
    <button onclick="closeModal('successModal')" class="w-full bg-zinc-100 text-zinc-600 py-3 rounded-full font-bold text-sm hover:bg-zinc-200 transition-colors">
      OK, Mengerti
    </button>
  </div>
</div>

<!-- Toast -->
<div id="toast" class="fixed top-24 right-4 z-[200] bg-green-600 text-white px-5 py-3 rounded-xl shadow-xl font-bold text-sm flex items-center gap-2 transition-all duration-300 translate-x-[120%]">
  <span class="material-symbols-outlined text-base">check_circle</span>
  <span id="toastMsg">Ditambahkan ke keranjang!</span>
</div>

<script>
const BASE_URL = <?= json_encode(BASE_URL) ?>;
const STORE_PHONE = '<?= $waPhone ?>';
let allProducts = [];
let filteredProducts = [];
let cart = JSON.parse(localStorage.getItem('fff_cart') || '[]');
let currentProduct = null;
let modalQty = 1;
let activeCategory = 'all';
let proofUrl = '';

// ===== Load Products =====
async function loadProducts() {
  try {
    const res = await fetch(BASE_URL + '/api/get_landing_products.php');
    const json = await res.json();
    if (json.success) {
      allProducts = json.data;
      buildCategoryChips();
      applyFilters();
    }
  } catch(e) { console.error(e); }
}

function fmtRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); }

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

// ===== Category Chips =====
function buildCategoryChips() {
  const cats = [...new Set(allProducts.map(p => p.category_name).filter(Boolean))].sort();
  const container = document.getElementById('categoryChips');
  let html = '<button class="category-chip active shrink-0 px-5 py-2.5 rounded-full text-sm font-bold transition-all" data-cat="all" onclick="filterCategory(\'all\')">Semua <span class="ml-1 text-xs opacity-70">(' + allProducts.length + ')</span></button>';
  cats.forEach(cat => {
    const count = allProducts.filter(p => p.category_name === cat).length;
    html += '<button class="category-chip shrink-0 px-5 py-2.5 rounded-full text-sm font-bold transition-all" data-cat="' + escHtml(cat) + '" onclick="filterCategory(\'' + escHtml(cat).replace(/'/g, "\\'") + '\')">' + escHtml(cat) + ' <span class="ml-1 text-xs opacity-70">(' + count + ')</span></button>';
  });
  container.innerHTML = html;
  updateChipStyles();
}

function filterCategory(cat) {
  activeCategory = cat;
  updateChipStyles();
  applyFilters();
}

function updateChipStyles() {
  document.querySelectorAll('.category-chip').forEach(chip => {
    const isActive = chip.dataset.cat === activeCategory;
    if (isActive) {
      chip.classList.add('bg-primary', 'text-white', 'shadow-lg', 'shadow-primary/20');
      chip.classList.remove('bg-zinc-100', 'text-zinc-600', 'hover:bg-zinc-200');
    } else {
      chip.classList.remove('bg-primary', 'text-white', 'shadow-lg', 'shadow-primary/20');
      chip.classList.add('bg-zinc-100', 'text-zinc-600', 'hover:bg-zinc-200');
    }
  });
}

// ===== Filtering & Sorting =====
function applyFilters() {
  const query = document.getElementById('searchInput').value.trim().toLowerCase();
  const sort = document.getElementById('sortSelect').value;
  const clearBtn = document.getElementById('clearSearch');

  clearBtn.classList.toggle('hidden', !query);

  filteredProducts = allProducts.filter(p => {
    const matchCat = activeCategory === 'all' || p.category_name === activeCategory;
    const matchSearch = !query || p.name.toLowerCase().includes(query) || (p.category_name || '').toLowerCase().includes(query) || (p.description || '').toLowerCase().includes(query);
    return matchCat && matchSearch;
  });

  // Sort
  filteredProducts.sort((a, b) => {
    switch (sort) {
      case 'name-asc': return a.name.localeCompare(b.name);
      case 'name-desc': return b.name.localeCompare(a.name);
      case 'price-asc': return a.price - b.price;
      case 'price-desc': return b.price - a.price;
      default: return 0;
    }
  });

  renderProducts(filteredProducts);
  document.getElementById('resultCount').textContent = filteredProducts.length + ' produk' + (query ? ' untuk "' + query + '"' : '');
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  activeCategory = 'all';
  document.getElementById('sortSelect').value = 'name-asc';
  updateChipStyles();
  applyFilters();
}

// Debounce search
let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applyFilters, 300);
});
document.getElementById('sortSelect').addEventListener('change', applyFilters);
document.getElementById('clearSearch').addEventListener('click', () => {
  document.getElementById('searchInput').value = '';
  applyFilters();
});

// ===== Render Products =====
function renderProducts(products) {
  const grid = document.getElementById('productGrid');
  const empty = document.getElementById('emptyState');

  if (!products.length) {
    grid.innerHTML = '';
    grid.classList.add('hidden');
    empty.classList.remove('hidden');
    return;
  }

  grid.classList.remove('hidden');
  empty.classList.add('hidden');

  grid.innerHTML = products.map(p => {
    const imgSrc = p.image_url || 'https://placehold.co/200x200/fdf2f0/c0392b?text=' + encodeURIComponent(p.name);
    const eName = escHtml(p.name);
    const eCat = escHtml(p.category_name || 'Produk');
    const inCart = cart.find(c => c.id === p.id);
    const cartBtnClass = inCart ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary';
    const cartBtnText = inCart ? '<span class="material-symbols-outlined text-sm" style="font-variation-settings:\'FILL\' 1">check_circle</span> Di Keranjang (' + inCart.qty + ')' : '<span class="material-symbols-outlined text-sm">add_shopping_cart</span> Tambah';

    return `
    <div class="group">
      <div class="bg-white rounded-2xl overflow-hidden shadow-[0_4px_20px_rgba(0,0,0,0.04)] hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer flex flex-col h-full" onclick="openProductModal(${p.id})">
        <div class="relative bg-surface-container-low aspect-square flex items-center justify-center p-4 overflow-hidden">
          <img alt="${eName}" class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500" src="${imgSrc}">
          <span class="absolute top-3 left-3 text-[10px] font-bold text-secondary-dim uppercase tracking-widest bg-white/90 backdrop-blur px-2.5 py-1 rounded-full">${eCat}</span>
          ${p.stock <= 5 ? '<span class="absolute top-3 right-3 text-[10px] font-bold text-red-600 bg-red-50 px-2 py-1 rounded-full">Sisa ' + p.stock + '</span>' : ''}
        </div>
        <div class="p-4 flex flex-col flex-1">
          <h4 class="font-headline font-bold text-red-900 text-sm md:text-base line-clamp-2 mb-2">${eName}</h4>
          <p class="text-lg font-black text-primary mt-auto">${fmtRp(p.price)}</p>
          <button onclick="event.stopPropagation();quickAdd(${p.id})" class="w-full ${cartBtnClass} py-2.5 rounded-full font-bold text-xs mt-3 transition-colors inline-flex items-center justify-center gap-1.5">
            ${cartBtnText}
          </button>
        </div>
      </div>
    </div>`;
  }).join('');
}

// ===== Product Detail Modal =====
function openProductModal(id) {
  const p = allProducts.find(x => x.id === id);
  if (!p) return;
  currentProduct = p;
  modalQty = 1;
  const imgSrc = p.image_url || 'https://placehold.co/300x300/fdf2f0/c0392b?text=' + encodeURIComponent(p.name);
  document.getElementById('pmImage').src = imgSrc;
  document.getElementById('pmName').textContent = p.name;
  document.getElementById('pmPrice').textContent = fmtRp(p.price);
  document.getElementById('pmCategory').textContent = p.category_name || 'Produk';
  document.getElementById('pmDesc').textContent = p.description || 'Frozen food berkualitas premium.';
  document.getElementById('pmStock').textContent = 'Stok: ' + p.stock + ' ' + (p.unit || 'pcs');
  document.getElementById('pmQty').textContent = '1';
  openModal('productModal');
}

function changeQty(delta) {
  modalQty = Math.max(1, Math.min(modalQty + delta, currentProduct ? currentProduct.stock : 99));
  document.getElementById('pmQty').textContent = modalQty;
}

function addToCartFromModal() {
  if (!currentProduct) return;
  addToCart(currentProduct.id, modalQty);
  closeModal('productModal');
  showToast(currentProduct.name + ' ditambahkan!');
}

function quickAdd(id) {
  const p = allProducts.find(x => x.id === id);
  if (!p) return;
  addToCart(id, 1);
  showToast(p.name + ' ditambahkan!');
  applyFilters(); // re-render to update button state
}

// ===== Cart Logic =====
function addToCart(productId, qty) {
  const existing = cart.find(c => c.id === productId);
  if (existing) { existing.qty += qty; } else { cart.push({ id: productId, qty }); }
  saveCart();
}

function removeFromCart(productId) {
  cart = cart.filter(c => c.id !== productId);
  saveCart(); renderCart(); applyFilters();
}

function updateCartQty(productId, qty) {
  const item = cart.find(c => c.id === productId);
  if (!item) return;
  if (qty <= 0) { removeFromCart(productId); return; }
  item.qty = qty;
  saveCart(); renderCart();
}

function saveCart() {
  localStorage.setItem('fff_cart', JSON.stringify(cart));
  updateBadge();
}

function updateBadge() {
  const total = cart.reduce((s, c) => s + c.qty, 0);
  ['cartBadge','cartBadgeMobile'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (total > 0) { el.classList.remove('hidden'); el.textContent = total; } else el.classList.add('hidden');
  });
}

function getCartTotal() {
  return cart.reduce((sum, c) => {
    const p = allProducts.find(x => x.id === c.id);
    return sum + (p ? p.price * c.qty : 0);
  }, 0);
}

// ===== Cart Modal =====
function openCartModal() { renderCart(); openModal('cartModal'); }

function renderCart() {
  const container = document.getElementById('cartItems');
  const footer = document.getElementById('cartFooter');
  if (!cart.length) {
    container.innerHTML = '<p class="text-center text-on-surface-variant py-8"><span class="material-symbols-outlined text-4xl block mb-2">shopping_cart</span>Keranjang kosong</p>';
    footer.classList.add('hidden'); return;
  }
  footer.classList.remove('hidden');
  container.innerHTML = cart.map(c => {
    const p = allProducts.find(x => x.id === c.id);
    if (!p) return '';
    const imgSrc = p.image_url || 'https://placehold.co/60x60/fdf2f0/c0392b?text=' + encodeURIComponent(p.name.charAt(0));
    return `
    <div class="flex items-center gap-3 bg-zinc-50 rounded-xl p-3">
      <img src="${imgSrc}" alt="${escHtml(p.name)}" class="w-14 h-14 object-contain rounded-lg bg-white">
      <div class="flex-1 min-w-0">
        <h4 class="font-bold text-sm text-red-900 truncate">${escHtml(p.name)}</h4>
        <p class="text-sm text-primary font-bold">${fmtRp(p.price)}</p>
      </div>
      <div class="flex items-center border border-zinc-200 rounded-full overflow-hidden bg-white">
        <button onclick="updateCartQty(${p.id},${c.qty-1})" class="px-2 py-1 hover:bg-zinc-100 text-xs"><span class="material-symbols-outlined text-sm">remove</span></button>
        <span class="px-2 font-bold text-sm">${c.qty}</span>
        <button onclick="updateCartQty(${p.id},${c.qty+1})" class="px-2 py-1 hover:bg-zinc-100 text-xs"><span class="material-symbols-outlined text-sm">add</span></button>
      </div>
      <button onclick="removeFromCart(${p.id})" class="text-zinc-400 hover:text-red-500 p-1"><span class="material-symbols-outlined text-sm">delete</span></button>
    </div>`;
  }).join('');
  document.getElementById('cartTotal').textContent = fmtRp(getCartTotal());
}

// ===== Checkout =====
function openCheckoutModal() {
  if (!cart.length) return;
  closeModal('cartModal');
  proofUrl = '';
  document.getElementById('proofSection').classList.add('hidden');
  document.getElementById('proofUploadArea').classList.remove('hidden');
  document.getElementById('proofPreview').classList.add('hidden');
  document.getElementById('proofUploading').classList.add('hidden');
  document.getElementById('proofInput').value = '';
  
  const btnSubmit = document.getElementById('btnSubmitOrder');
  if(btnSubmit) btnSubmit.classList.add('hidden');

  const itemsHtml = cart.map(c => {
    const p = allProducts.find(x => x.id === c.id);
    if (!p) return '';
    return '<div class="flex justify-between"><span>' + escHtml(p.name) + ' x' + c.qty + '</span><span class="font-bold">' + fmtRp(p.price * c.qty) + '</span></div>';
  }).join('');
  document.getElementById('checkoutItems').innerHTML = itemsHtml;
  document.getElementById('checkoutTotal').textContent = fmtRp(getCartTotal());
  openModal('checkoutModal');
  togglePayMethod();
}

function togglePayMethod() {
  const method = document.querySelector('input[name="payMethod"]:checked');
  const payMethod = method ? method.value : 'qris';
  
  const qrisDetail = document.getElementById('qrisDetail');
  const bankDetail = document.getElementById('bankDetail');
  const codDetail  = document.getElementById('codDetail');
  const proofSec   = document.getElementById('proofSection');
  const btnSubmit  = document.getElementById('btnSubmitOrder');

  if (qrisDetail) qrisDetail.classList.add('hidden');
  if (bankDetail) bankDetail.classList.add('hidden');
  if (codDetail)  codDetail.classList.add('hidden');

  if (payMethod === 'qris') {
    if (qrisDetail) qrisDetail.classList.remove('hidden');
    if (proofSec) proofSec.classList.remove('hidden');
    if (btnSubmit) {
      if (proofUrl) btnSubmit.classList.remove('hidden');
      else btnSubmit.classList.add('hidden');
    }
  } else if (payMethod === 'bank') {
    if (bankDetail) bankDetail.classList.remove('hidden');
    if (proofSec) proofSec.classList.remove('hidden');
    if (btnSubmit) {
      if (proofUrl) btnSubmit.classList.remove('hidden');
      else btnSubmit.classList.add('hidden');
    }
  } else if (payMethod === 'cod') {
    if (codDetail) codDetail.classList.remove('hidden');
    if (proofSec) proofSec.classList.add('hidden');
    if (btnSubmit) btnSubmit.classList.remove('hidden');
  }
}

document.addEventListener('DOMContentLoaded', togglePayMethod);

function handleProofUpload(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2*1024*1024) { showToast('File terlalu besar! Maks 2MB', true); return; }
  if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { showToast('Hanya JPG/PNG/WebP!', true); return; }
  document.getElementById('proofUploadArea').classList.add('hidden');
  document.getElementById('proofUploading').classList.remove('hidden');
  const fd = new FormData();
  fd.append('proof', file);
  fetch(BASE_URL + '/api/upload_proof.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      document.getElementById('proofUploading').classList.add('hidden');
      if (json.success) {
        proofUrl = json.url;
        document.getElementById('proofThumb').src = URL.createObjectURL(file);
        document.getElementById('proofFilename').textContent = file.name;
        document.getElementById('proofPreview').classList.remove('hidden');
        document.getElementById('btnSubmitOrder').classList.remove('hidden');
      } else { showToast(json.message || 'Upload gagal!', true); document.getElementById('proofUploadArea').classList.remove('hidden'); }
    })
    .catch(() => { document.getElementById('proofUploading').classList.add('hidden'); document.getElementById('proofUploadArea').classList.remove('hidden'); showToast('Upload gagal, coba lagi!', true); });
}

function removeProof() {
  proofUrl = '';
  document.getElementById('proofInput').value = '';
  document.getElementById('proofPreview').classList.add('hidden');
  document.getElementById('proofUploadArea').classList.remove('hidden');
  
  const method = document.querySelector('input[name="payMethod"]:checked');
  const payMethod = method ? method.value : 'qris';
  if (payMethod !== 'cod') {
    document.getElementById('btnSubmitOrder').classList.add('hidden');
  }
}

function submitOrder() {
  const name = document.getElementById('custName').value.trim();
  const phone = document.getElementById('custPhone').value.trim();
  const address = document.getElementById('custAddress').value.trim();
  if (!name || !phone || !address) { showToast('Lengkapi data pemesan!', true); return; }
  const method = document.querySelector('input[name="payMethod"]:checked');
  const payMethod = method ? method.value : 'qris';

  if (payMethod !== 'cod' && !proofUrl) {
    showToast('Upload bukti pembayaran dulu!', true);
    return;
  }
  const btn = document.getElementById('btnSubmitOrder');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 inline mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Mengirim...';
  fetch(BASE_URL + '/api/submit_order.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ customer_name: name, customer_phone: phone, customer_address: address, payment_method: payMethod, proof_image: proofUrl, items: cart.map(c => ({ id: c.id, qty: c.qty })) })
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined text-base">send</span> Kirim Pesanan';
    if (!data.success) { showToast(data.message, true); return; }
    var custName = document.getElementById('custName').value.trim();
    cart = []; proofUrl = ''; saveCart(); closeModal('checkoutModal'); updateBadge(); applyFilters();
    showOrderSuccess(data.order_no, data.total, custName);
  })
  .catch(() => { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined text-base">send</span> Kirim Pesanan'; showToast('Gagal mengirim pesanan, coba lagi!', true); });
}

// ===== Modal Helpers =====
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); if (!document.querySelector('#productModal:not(.hidden), #cartModal:not(.hidden), #checkoutModal:not(.hidden), #successModal:not(.hidden)')) document.body.style.overflow = ''; }

function showOrderSuccess(orderNo, total, custName) {
  document.getElementById('successOrderNo').textContent = orderNo;
  document.getElementById('successTotal').textContent = fmtRp(total);
  // Build WhatsApp link
  var waMsg = 'Halo Admin, saya ingin konfirmasi pesanan:%0A%0A' +
    '📋 No. Order: ' + encodeURIComponent(orderNo) + '%0A' +
    '💰 Total: ' + encodeURIComponent(fmtRp(total)) + '%0A' +
    '👤 Nama: ' + encodeURIComponent(custName || '-') + '%0A%0A' +
    'Mohon diproses, terima kasih! 🙏';
  var waBtn = document.getElementById('successWaBtn');
  if (waBtn && STORE_PHONE) {
    waBtn.href = 'https://wa.me/' + STORE_PHONE + '?text=' + waMsg;
  }
  openModal('successModal');
}

function showToast(msg, isError) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.className = t.className.replace('bg-green-600','').replace('bg-red-600','');
  t.classList.add(isError ? 'bg-red-600' : 'bg-green-600');
  t.style.transform = 'translateX(0)';
  setTimeout(() => { t.style.transform = 'translateX(120%)'; }, 2500);
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => { loadProducts(); updateBadge(); });
</script>

<div style="height:80px" class="md:hidden"></div>
</body>
</html>

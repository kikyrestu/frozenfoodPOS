<?php
require_once __DIR__ . '/config/config.php';
$storeName    = getSetting('store_name') ?: 'Fun Frozen Food';
$storeAddress = getSetting('store_address') ?: '';
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
<title><?= htmlspecialchars($storeName) ?> — Frozen Food Terbaik</title>
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<meta name="theme-color" content="#c0392b">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@300;400;500;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "surface-variant":"#d9dedb","inverse-primary":"#ffa4a4","on-secondary":"#fff0e6",
        "on-secondary-fixed":"#522a00","surface-container-low":"#fdf2f0","surface-container-lowest":"#ffffff",
        "surface-container-highest":"#f0ddd9","secondary-fixed":"#ffc698","outline":"#747776",
        "on-tertiary-container":"#601500","primary-fixed-dim":"#ff8a8a","surface-dim":"#e8d5d1",
        "inverse-surface":"#1a0e0b","tertiary-dim":"#962600","on-error":"#ffefec",
        "on-background":"#2c2222","surface-container-high":"#f5e3df","background":"#fef7f5",
        "surface-tint":"#c0392b","secondary":"#8b4b00","inverse-on-surface":"#9b9d9c",
        "on-primary-fixed-variant":"#a02d20","tertiary-fixed":"#ff9475","on-secondary-container":"#6e3a00",
        "outline-variant":"#d4a8a0","error-dim":"#b92902","surface-container":"#f8e8e5",
        "on-secondary-fixed-variant":"#7b4200","on-tertiary-fixed-variant":"#6f1a00",
        "secondary-dim":"#7a4100","tertiary-fixed-dim":"#ff7d57","primary-fixed":"#ffb4b0",
        "on-surface":"#2c2222","error-container":"#f95630","error":"#b02500",
        "primary-dim":"#a02d20","tertiary":"#ab2d00","tertiary-container":"#ff9475",
        "surface-bright":"#fef7f5","on-tertiary":"#ffefec","on-tertiary-fixed":"#340700",
        "on-primary-fixed":"#5c0f08","on-primary-container":"#8b2218","surface":"#fef7f5",
        "secondary-fixed-dim":"#ffb472","secondary-container":"#ffc698",
        "on-surface-variant":"#6b5552","on-error-container":"#520c00",
        "primary":"#c0392b","primary-container":"#ffb4b0","on-primary":"#ffffff"
      },
      fontFamily: {
        "headline":["Plus Jakarta Sans"],
        "body":["Be Vietnam Pro"],
        "label":["Be Vietnam Pro"]
      },
      borderRadius:{"DEFAULT":"1rem","lg":"2rem","xl":"3rem","full":"9999px"},
    },
  },
}
</script>
<style>
  .material-symbols-outlined { font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
  .scrollbar-hide::-webkit-scrollbar { display:none; }
  .scrollbar-hide { -ms-overflow-style:none; scrollbar-width:none; }
  html { scroll-behavior:smooth; }
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
    <a class="text-red-800 font-bold" href="<?= BASE_URL ?>/">Home</a>
    <a class="text-zinc-600 hover:text-red-800 transition-transform duration-300 hover:scale-105" href="<?= BASE_URL ?>/products.php">Katalog</a>
    <a class="text-zinc-600 hover:text-red-800 transition-transform duration-300 hover:scale-105" href="#about">Tentang Kami</a>
    <a class="text-zinc-600 hover:text-red-800 transition-transform duration-300 hover:scale-105" href="#contact">Kontak</a>
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

<!-- Hero Section -->
<section class="relative h-[750px] md:h-[870px] flex items-center overflow-hidden bg-surface-container-low">
  <div class="absolute inset-0 z-0">
    <img alt="Frozen food display" class="w-full h-full object-cover object-center opacity-90"
         src="https://lh3.googleusercontent.com/aida-public/AB6AXuBq0d5GbQ8U6SPVsobm40MJOsRJ7DNrGevEIHv0KXOtm4T-bVNUZ-kkNzZRkhQLCQ52gX8tnnsCWTRShL653r9AESxQvfcmziRX-LRwG2NCynOEo-QiSNDdX2JM7mnyfRlHPkSfaR5X6youdcVoOdFG_gA6ZRzZcQcBgQsOYq5E817rw4D8G3nwuwUjUmyRvfLHCjyiercjUYzfF_u4wAHthSrMiyQkQbA2uqMjZyt5kDIqwFxWlAWGRIGJUScX4Ga-LvNVEehYLjc"/>
    <div class="absolute inset-0 bg-gradient-to-r from-background/95 via-background/50 to-transparent"></div>
  </div>
  <div class="container mx-auto px-6 md:px-8 relative z-10">
    <div class="max-w-2xl space-y-6">
      <span class="inline-block py-1.5 px-4 rounded-full bg-primary-container text-on-primary-container font-label text-sm font-bold uppercase tracking-widest">Frozen Food Premium</span>
      <h1 class="text-5xl md:text-8xl font-headline font-extrabold tracking-tighter text-red-900 leading-[0.9]">
        Segar &amp; Lezat <br/><span class="text-primary italic">Setiap Saat</span>
      </h1>
      <p class="text-lg md:text-xl text-on-surface-variant max-w-lg font-light leading-relaxed">
        Nikmati kelezatan frozen food berkualitas premium. Diproses dengan teknologi modern untuk menjaga cita rasa dan nutrisi terbaik.
      </p>
      <div class="flex flex-wrap gap-4 pt-4">
        <a href="#products" class="bg-primary text-on-primary px-8 md:px-10 py-4 md:py-5 rounded-full font-bold text-lg shadow-xl shadow-primary/20 transition-all hover:scale-105 active:scale-95 inline-flex items-center gap-2">
          <span class="material-symbols-outlined">shopping_basket</span> Lihat Produk
        </a>
        <a href="#about" class="bg-surface-container-lowest text-primary px-8 md:px-10 py-4 md:py-5 rounded-full font-bold text-lg border border-primary/10 transition-all hover:bg-surface-container-low inline-flex items-center gap-2">
          <span class="material-symbols-outlined">play_circle</span> Cerita Kami
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Category Bento Grid -->
<section id="categories" class="py-20 md:py-24 px-6 md:px-8 max-w-screen-2xl mx-auto">
  <div class="flex flex-col md:flex-row justify-between items-end mb-12 md:mb-16 gap-6">
    <div>
      <h2 class="text-4xl md:text-5xl font-headline font-bold text-red-900 tracking-tight">Kategori Produk</h2>
      <p class="text-on-surface-variant mt-2">Pilihan frozen food terlengkap untuk keluarga Anda.</p>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-12 gap-6 md:gap-8">
    <div class="md:col-span-7 group relative h-[350px] md:h-[500px] rounded-lg overflow-hidden bg-surface-container-highest cursor-pointer">
      <img alt="Dim Sum & Dumplings" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
           src="https://lh3.googleusercontent.com/aida-public/AB6AXuDl8yFvBtGkTTqfBoHXes_huY-aM7jVOKLkT53jTbE86MMkxvxWkhJ6PVjE2SlfUnHik0mU3IdduGYWxgZ07qR1V6rnwKS7O2V8vvjSO_vIrDd5XsLftKetkvqTgEMvaJcfChCCC6LJGnnv7og_5-ymyk9wx_qqk56qqbepYVoDYFJb802lGnr5ltuAUAisQzzAX62er1lQv7hvCZF-l596cK1T35Ym4Q6r4Xo50YtI1CBAqIk_-1sj2bJ5uocgXUL4R1XgDSF3fuM"/>
      <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
      <div class="absolute bottom-8 md:bottom-10 left-8 md:left-10 text-white">
        <h3 class="text-3xl md:text-4xl font-headline font-extrabold tracking-tight">Dim Sum &amp; Siomay</h3>
        <p class="mt-2 text-white/80">Kelezatan tradisional dalam setiap gigitan.</p>
      </div>
    </div>
    <div class="md:col-span-5 flex flex-col gap-6 md:gap-8">
      <div class="h-[220px] md:h-1/2 group relative rounded-lg overflow-hidden bg-surface-container-highest cursor-pointer">
        <img alt="Nugget & Crispy" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
             src="https://lh3.googleusercontent.com/aida-public/AB6AXuCJqbBe8nS0Oc-dpthOVVJmYMfG7vv5r5xBD375-Gfe3qVDkSILT7az1ph4-BN9zFbOzVbQqW8tgNQrM4Pm-VrHlVOas146f2nNxcqO5V-rg78oYrGgGkAS9us_HkQK6R_uetXBE3n2I4kbIMRjHvzG2mMqilLmxAb9XUwi3kKCSTApErIXn1mVxmL4jTneMJ4yZaE62trAq2Icr-sxXJbHpMSLdxV1lyhN32Ea7thrEvhOhvXGbtto_lXySxjmWhPaf5SszSZZfC0"/>
        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
        <div class="absolute bottom-6 left-8 text-white">
          <h3 class="text-2xl font-headline font-bold">Nugget &amp; Crispy</h3>
        </div>
      </div>
      <div class="h-[220px] md:h-1/2 group relative rounded-lg overflow-hidden bg-surface-container-highest cursor-pointer">
        <img alt="Sosis & Bakso" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
             src="https://lh3.googleusercontent.com/aida-public/AB6AXuBXcde8ne0WY4BABlSHkjBoyREYXxDMBFbSokBHKDpz4vMXk0QjK_L64gYuRzZNaCD3N8qEbqrJ7PesRRgWaVpceeO5vissuf9ScL3s0XSogxlyLnKK6iFu744KmtqrV4zDFSJHZbFr1ub3AbwUVz3yZ9jswjCZawYvU5jsUt_6R0bF3D-9D0cLe5nLUA5e8Yh_OC-JXW3h1kpdOTYDSTGCcqnHJ88MjOZ0sy-xVinsICZjMcXmEGfidsiGN8T9mQQLA5TEPNDwjQ8"/>
        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
        <div class="absolute bottom-6 left-8 text-white">
          <h3 class="text-2xl font-headline font-bold">Sosis &amp; Bakso</h3>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Products Section -->
<section id="products" class="bg-surface-container-low py-24 md:py-32 px-6 md:px-8">
  <div class="max-w-screen-2xl mx-auto">
    <div class="text-center space-y-4 mb-16 md:mb-20">
      <span class="text-secondary font-bold tracking-[0.2em] uppercase text-xs">Produk Unggulan</span>
      <h2 class="text-4xl md:text-5xl font-headline font-black text-red-950 tracking-tighter">Pilihan Terlaris Minggu Ini</h2>
      <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 md:gap-10" id="productGrid">
      <!-- Products loaded dynamically -->
      <div class="col-span-full text-center py-12 text-on-surface-variant">
        <span class="material-symbols-outlined text-4xl animate-spin">progress_activity</span>
        <p class="mt-2">Memuat produk...</p>
      </div>
    </div>
  </div>
</section>

<!-- About / Why Choose Us -->
<section id="about" class="py-24 md:py-32 px-6 md:px-8 max-w-screen-2xl mx-auto">
  <div class="text-center space-y-4 mb-16">
    <span class="text-secondary font-bold tracking-[0.2em] uppercase text-xs">Kenapa Pilih Kami</span>
    <h2 class="text-4xl md:text-5xl font-headline font-black text-red-950 tracking-tighter">Kualitas Adalah Segalanya</h2>
    <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="bg-surface-container-lowest rounded-lg p-8 text-center shadow-[0_8px_30px_rgba(192,57,43,0.04)] hover:shadow-xl transition-shadow">
      <div class="w-16 h-16 rounded-full bg-primary-container flex items-center justify-center mx-auto mb-5">
        <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings:'FILL' 1">verified</span>
      </div>
      <h3 class="text-xl font-headline font-bold text-red-900 mb-3">100% Halal & BPOM</h3>
      <p class="text-on-surface-variant text-sm leading-relaxed">Semua produk kami telah tersertifikasi halal MUI dan terdaftar di BPOM untuk keamanan keluarga Anda.</p>
    </div>
    <div class="bg-surface-container-lowest rounded-lg p-8 text-center shadow-[0_8px_30px_rgba(192,57,43,0.04)] hover:shadow-xl transition-shadow">
      <div class="w-16 h-16 rounded-full bg-primary-container flex items-center justify-center mx-auto mb-5">
        <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings:'FILL' 1">ac_unit</span>
      </div>
      <h3 class="text-xl font-headline font-bold text-red-900 mb-3">Flash Freeze Technology</h3>
      <p class="text-on-surface-variant text-sm leading-relaxed">Dibekukan dengan teknologi flash freeze untuk menjaga kesegaran, rasa, dan nutrisi tetap optimal.</p>
    </div>
    <div class="bg-surface-container-lowest rounded-lg p-8 text-center shadow-[0_8px_30px_rgba(192,57,43,0.04)] hover:shadow-xl transition-shadow">
      <div class="w-16 h-16 rounded-full bg-primary-container flex items-center justify-center mx-auto mb-5">
        <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings:'FILL' 1">local_shipping</span>
      </div>
      <h3 class="text-xl font-headline font-bold text-red-900 mb-3">Pengiriman Cepat</h3>
      <p class="text-on-surface-variant text-sm leading-relaxed">Dikirim dengan cooler box khusus untuk memastikan produk sampai dalam kondisi beku sempurna.</p>
    </div>
  </div>
</section>

<!-- Gallery / TikTok Feed -->
<section class="py-24 md:py-32 px-6 md:px-8 max-w-screen-2xl mx-auto overflow-hidden">
  <div class="text-center mb-14">
    <div class="inline-flex items-center gap-3 mb-4">
      <svg class="w-9 h-9" viewBox="0 0 24 24" fill="none"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.84a4.84 4.84 0 01-1-.15z" fill="currentColor"/></svg>
      <h3 class="text-3xl md:text-4xl font-headline font-bold text-red-900">Follow Kami di TikTok</h3>
    </div>
    <a href="https://www.tiktok.com/@funfrozenfood1" target="_blank" class="text-zinc-800 hover:text-black font-bold text-lg transition-colors">@funfrozenfood1</a>
    <p class="text-on-surface-variant mt-2">Tonton video terbaru &amp; resep frozen food dari kami</p>
  </div>

  <!-- Phone Frame -->
  <div class="flex justify-center">
    <div class="relative mx-auto" style="width:380px; max-width:92vw;">
      <!-- Phone outer shell -->
      <div class="rounded-[3rem] bg-gradient-to-b from-zinc-700 via-zinc-900 to-zinc-800 p-[10px] shadow-[0_25px_100px_rgba(0,0,0,0.4)]">
        <!-- Phone inner bezel -->
        <div class="rounded-[2.3rem] bg-black p-[3px] relative">
          <!-- Screen -->
          <div class="rounded-[2.1rem] overflow-hidden bg-white relative" style="height:720px;">
            <!-- Status bar -->
            <div class="bg-white flex items-center justify-between px-8 pt-3 pb-1">
              <span class="text-[11px] font-bold text-black">9:41</span>
              <div class="w-24 h-[26px] bg-black rounded-full"></div>
              <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-black" fill="currentColor" viewBox="0 0 24 24"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
                <svg class="w-4 h-4 text-black" fill="currentColor" viewBox="0 0 24 24"><path d="M15.67 4H14V2h-4v2H8.33C7.6 4 7 4.6 7 5.33v15.33C7 21.4 7.6 22 8.33 22h7.33c.74 0 1.34-.6 1.34-1.33V5.33C17 4.6 16.4 4 15.67 4z"/></svg>
              </div>
            </div>
            <!-- TikTok embed inside screen -->
            <div class="w-full overflow-y-auto" id="tiktok-feed" style="height:calc(100% - 55px);">
              <blockquote class="tiktok-embed" cite="https://www.tiktok.com/@funfrozenfood1" data-unique-id="funfrozenfood1" data-embed-from="embed_page" data-embed-type="creator"
                style="max-width:100%;min-width:100%;margin:0;border:none;">
                <section><a target="_blank" href="https://www.tiktok.com/@funfrozenfood1">@funfrozenfood1</a></section>
              </blockquote>
            </div>
            <!-- Bottom home bar -->
            <div class="absolute bottom-0 left-0 right-0 bg-white flex justify-center pb-2 pt-2">
              <div class="w-32 h-1 bg-zinc-300 rounded-full"></div>
            </div>
          </div>
        </div>
      </div>
      <!-- Side buttons -->
      <div class="absolute top-32 -left-[2px] w-[3px] h-8 bg-zinc-700 rounded-l-sm"></div>
      <div class="absolute top-44 -left-[2px] w-[3px] h-14 bg-zinc-700 rounded-l-sm"></div>
      <div class="absolute top-60 -left-[2px] w-[3px] h-14 bg-zinc-700 rounded-l-sm"></div>
      <div class="absolute top-40 -right-[2px] w-[3px] h-16 bg-zinc-700 rounded-r-sm"></div>
      <!-- Reflection -->
      <div class="absolute inset-0 rounded-[3rem] pointer-events-none bg-gradient-to-br from-white/8 via-transparent to-transparent"></div>
    </div>
  </div>

  <div class="text-center mt-14">
    <a href="https://www.tiktok.com/@funfrozenfood1" target="_blank"
       class="inline-flex items-center gap-3 bg-black text-white px-10 py-5 rounded-full font-bold text-lg shadow-xl transition-all hover:scale-105 active:scale-95 hover:shadow-2xl">
      <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.84a4.84 4.84 0 01-1-.15z" fill="white"/></svg>
      Follow @funfrozenfood1
    </a>
  </div>
</section>
<script async src="https://www.tiktok.com/embed.js"></script>
<style>
  /* Make TikTok embed fill phone screen */
  #tiktok-feed iframe { width:100%!important; border:none!important; display:block; }
  #tiktok-feed blockquote { margin:0!important; padding:0!important; border:none!important; max-width:100%!important; }
</style>

<!-- Contact / CTA Section -->
<section id="contact" class="bg-primary py-20 md:py-24 px-6 md:px-8">
  <div class="max-w-screen-xl mx-auto text-center text-white space-y-6">
    <h2 class="text-4xl md:text-5xl font-headline font-black tracking-tight">Pesan Sekarang!</h2>
    <p class="text-white/80 text-lg max-w-2xl mx-auto">Hubungi kami via WhatsApp untuk pemesanan, info produk, dan harga grosir. Kami siap melayani Anda!</p>
    <div class="flex flex-wrap justify-center gap-4 pt-4">
      <a href="https://wa.me/<?= $waPhone ?>?text=Halo,%20saya%20ingin%20pesan%20frozen%20food" target="_blank"
         class="bg-white text-primary px-10 py-5 rounded-full font-bold text-lg shadow-xl transition-all hover:scale-105 active:scale-95 inline-flex items-center gap-3">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        Hubungi via WhatsApp
      </a>
    </div>
    <?php if ($storeAddress): ?>
    <p class="text-white/60 text-sm pt-6">
      <span class="material-symbols-outlined text-base align-middle">location_on</span>
      <?= htmlspecialchars($storeAddress) ?>
    </p>
    <?php endif; ?>
  </div>
</section>
</main>

<!-- Footer -->
<footer class="w-full py-10 bg-zinc-100 border-t border-zinc-200">
  <div class="max-w-screen-2xl mx-auto px-6 md:px-8 flex flex-col md:flex-row justify-between items-center gap-4 font-label text-xs uppercase tracking-widest text-red-900">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?>. All rights reserved.</p>
    <div class="flex gap-8">
      <a class="text-zinc-400 hover:text-red-600 transition-colors" href="<?= BASE_URL ?>/products.php">Katalog</a>
      <a class="text-zinc-400 hover:text-red-600 transition-colors" href="#about">Tentang</a>
      <a class="text-zinc-400 hover:text-red-600 transition-colors" href="#contact">Kontak</a>
    </div>
  </div>
</footer>

<!-- Mobile Bottom Navigation -->
<nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-lg flex justify-around items-center py-3 px-6 z-50 border-t border-zinc-100">
  <a href="<?= BASE_URL ?>/" class="flex flex-col items-center gap-1 text-red-800">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">home</span>
    <span class="text-[10px] font-bold">Home</span>
  </a>
  <a href="<?= BASE_URL ?>/products.php" class="flex flex-col items-center gap-1 text-zinc-400">
    <span class="material-symbols-outlined">storefront</span>
    <span class="text-[10px] font-bold">Katalog</span>
  </a>
  <button onclick="openCartModal()" class="flex flex-col items-center gap-1 text-zinc-400 relative">
    <span class="material-symbols-outlined">shopping_cart</span>
    <span class="text-[10px] font-bold">Keranjang</span>
    <span id="cartBadgeMobile" class="hidden absolute -top-1 right-1 bg-primary text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
  </button>
  <a href="#about" class="flex flex-col items-center gap-1 text-zinc-400">
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
          <span class="material-symbols-outlined text-base">add_shopping_cart</span> Tambah ke Keranjang
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
      <!-- Order Summary -->
      <div>
        <h4 class="font-bold text-sm text-zinc-500 uppercase tracking-wider mb-3">Ringkasan Pesanan</h4>
        <div id="checkoutItems" class="space-y-2 text-sm"></div>
        <div class="flex justify-between items-center mt-3 pt-3 border-t border-zinc-100">
          <span class="font-bold">Total Bayar</span>
          <span id="checkoutTotal" class="text-xl font-black text-primary">Rp 0</span>
        </div>
      </div>

      <!-- Customer Info -->
      <div>
        <h4 class="font-bold text-sm text-zinc-500 uppercase tracking-wider mb-3">Data Pemesan</h4>
        <div class="space-y-3">
          <input type="text" id="custName" placeholder="Nama lengkap *" class="w-full border border-zinc-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <input type="text" id="custPhone" placeholder="No. WhatsApp * (08xxx)" class="w-full border border-zinc-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none">
          <textarea id="custAddress" rows="2" placeholder="Alamat pengiriman *" class="w-full border border-zinc-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-none"></textarea>
        </div>
      </div>

      <!-- Payment Method -->
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

        <!-- QRIS Detail -->
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

      <!-- Upload Bukti Pembayaran -->
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

<!-- Toast Notification -->
<div id="toast" class="fixed top-24 right-4 z-[200] bg-green-600 text-white px-5 py-3 rounded-xl shadow-xl font-bold text-sm flex items-center gap-2 transition-all duration-300 translate-x-[120%]">
  <span class="material-symbols-outlined text-base">check_circle</span>
  <span id="toastMsg">Ditambahkan ke keranjang!</span>
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

<script>
const BASE_URL = <?= json_encode(BASE_URL) ?>;
const STORE_PHONE = '<?= $waPhone ?>';
let allProducts = [];
let cart = JSON.parse(localStorage.getItem('fff_cart') || '[]');
let currentProduct = null;
let modalQty = 1;

// ===== Load Products =====
async function loadProducts() {
  try {
    const res = await fetch(BASE_URL + '/api/get_landing_products.php');
    const json = await res.json();
    if (json.success) {
      allProducts = json.data;
      renderProducts(allProducts);
    }
  } catch(e) { console.error(e); }
}

function fmtRp(n) { return 'Rp ' + n.toLocaleString('id-ID'); }

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

function renderProducts(products) {
  const grid = document.getElementById('productGrid');
  if (!products.length) {
    grid.innerHTML = '<p class="col-span-full text-center text-on-surface-variant py-12">Belum ada produk tersedia.</p>';
    return;
  }
  grid.innerHTML = products.map(p => {
    const imgSrc = p.image_url || 'https://placehold.co/200x200/fdf2f0/c0392b?text=' + encodeURIComponent(p.name);
    const eName = escHtml(p.name);
    const eCat = escHtml(p.category_name || 'Produk');
    const eDesc = escHtml(p.description || 'Frozen food berkualitas premium.');
    return `
    <div class="group pt-12">
      <div class="bg-surface-container-lowest rounded-lg p-6 pt-12 relative shadow-[0_12px_40px_rgba(192,57,43,0.04)] transition-all hover:scale-[1.02] hover:shadow-2xl cursor-pointer" onclick="openProductModal(${p.id})">
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 drop-shadow-2xl group-hover:-translate-y-4 transition-transform duration-500">
          <img alt="${eName}" class="w-full h-full object-contain" src="${imgSrc}">
        </div>
        <div class="mt-32 space-y-3">
          <div class="flex justify-between items-start">
            <div>
              <span class="text-[10px] font-bold text-secondary-dim uppercase tracking-widest bg-secondary-container/30 px-2 py-0.5 rounded">${eCat}</span>
              <h4 class="text-xl font-headline font-bold text-red-900 mt-1">${eName}</h4>
            </div>
            <span class="text-lg font-black text-primary whitespace-nowrap ml-2">${fmtRp(p.price)}</span>
          </div>
          <p class="text-sm text-on-surface-variant line-clamp-2">${eDesc}</p>
          <button onclick="event.stopPropagation();quickAdd(${p.id})" class="w-full bg-primary-container text-on-primary-container py-3 rounded-full font-bold text-sm mt-4 hover:bg-primary transition-colors hover:text-on-primary inline-flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-base">add_shopping_cart</span> Tambah ke Keranjang
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
}

// ===== Cart Logic =====
function addToCart(productId, qty) {
  const existing = cart.find(c => c.id === productId);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({ id: productId, qty: qty });
  }
  saveCart();
}

function removeFromCart(productId) {
  cart = cart.filter(c => c.id !== productId);
  saveCart();
  renderCart();
}

function updateCartQty(productId, qty) {
  const item = cart.find(c => c.id === productId);
  if (!item) return;
  if (qty <= 0) { removeFromCart(productId); return; }
  item.qty = qty;
  saveCart();
  renderCart();
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
    if (total > 0) { el.classList.remove('hidden'); el.textContent = total; }
    else el.classList.add('hidden');
  });
}

function getCartTotal() {
  return cart.reduce((sum, c) => {
    const p = allProducts.find(x => x.id === c.id);
    return sum + (p ? p.price * c.qty : 0);
  }, 0);
}

// ===== Cart Modal =====
function openCartModal() {
  renderCart();
  openModal('cartModal');
}

function renderCart() {
  const container = document.getElementById('cartItems');
  const footer = document.getElementById('cartFooter');
  if (!cart.length) {
    container.innerHTML = '<p class="text-center text-on-surface-variant py-8"><span class="material-symbols-outlined text-4xl block mb-2">shopping_cart</span>Keranjang kosong</p>';
    footer.classList.add('hidden');
    return;
  }
  footer.classList.remove('hidden');
  container.innerHTML = cart.map(c => {
    const p = allProducts.find(x => x.id === c.id);
    if (!p) return '';
    const imgSrc = p.image_url || 'https://placehold.co/60x60/fdf2f0/c0392b?text=' + encodeURIComponent(p.name.charAt(0));
    const eName = escHtml(p.name);
    return `
    <div class="flex items-center gap-3 bg-zinc-50 rounded-xl p-3">
      <img src="${imgSrc}" alt="${eName}" class="w-14 h-14 object-contain rounded-lg bg-white">
      <div class="flex-1 min-w-0">
        <h4 class="font-bold text-sm text-red-900 truncate">${eName}</h4>
        <p class="text-sm text-primary font-bold">${fmtRp(p.price)}</p>
      </div>
      <div class="flex items-center border border-zinc-200 rounded-full overflow-hidden bg-white">
        <button onclick="updateCartQty(${p.id},${c.qty - 1})" class="px-2 py-1 hover:bg-zinc-100 text-xs"><span class="material-symbols-outlined text-sm">remove</span></button>
        <span class="px-2 font-bold text-sm">${c.qty}</span>
        <button onclick="updateCartQty(${p.id},${c.qty + 1})" class="px-2 py-1 hover:bg-zinc-100 text-xs"><span class="material-symbols-outlined text-sm">add</span></button>
      </div>
      <button onclick="removeFromCart(${p.id})" class="text-zinc-400 hover:text-red-500 p-1"><span class="material-symbols-outlined text-sm">delete</span></button>
    </div>`;
  }).join('');
  document.getElementById('cartTotal').textContent = fmtRp(getCartTotal());
}

// ===== Checkout Modal =====
function openCheckoutModal() {
  if (!cart.length) return;
  closeModal('cartModal');
  // Reset proof state
  proofUrl = '';
  document.getElementById('proofSection').classList.add('hidden');
  document.getElementById('proofUploadArea').classList.remove('hidden');
  document.getElementById('proofPreview').classList.add('hidden');
  document.getElementById('proofUploading').classList.add('hidden');
  document.getElementById('proofInput').value = '';
  
  const btnSubmit = document.getElementById('btnSubmitOrder');
  if(btnSubmit) btnSubmit.classList.add('hidden');

  // Render order summary
  const itemsHtml = cart.map(c => {
    const p = allProducts.find(x => x.id === c.id);
    if (!p) return '';
    return `<div class="flex justify-between"><span>${p.name} x${c.qty}</span><span class="font-bold">${fmtRp(p.price * c.qty)}</span></div>`;
  }).join('');
  document.getElementById('checkoutItems').innerHTML = itemsHtml;
  document.getElementById('checkoutTotal').textContent = fmtRp(getCartTotal());
  openModal('checkoutModal');
  togglePayMethod();
}

function togglePaymentDetails() {
  const selected = document.querySelector('input[name="payMethod"]:checked');
  if (!selected) return;
  const qd = document.getElementById('qrisDetail');
  const bd = document.getElementById('bankDetail');
  if (qd) qd.classList.toggle('hidden', selected.value !== 'qris');
  if (bd) bd.classList.toggle('hidden', selected.value !== 'bank');
}

document.querySelectorAll('input[name="payMethod"]').forEach(r => r.addEventListener('change', togglePaymentDetails));

function copyText(text) {
  navigator.clipboard.writeText(text).then(() => showToast('Nomor rekening disalin!'));
}

let proofUrl = '';

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

// Call on load to ensure correct state initially
document.addEventListener('DOMContentLoaded', togglePayMethod);

function handleProofUpload(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { showToast('File terlalu besar! Maks 2MB', true); return; }
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
      } else {
        showToast(json.message || 'Upload gagal!', true);
        document.getElementById('proofUploadArea').classList.remove('hidden');
      }
    })
    .catch(() => {
      document.getElementById('proofUploading').classList.add('hidden');
      document.getElementById('proofUploadArea').classList.remove('hidden');
      showToast('Upload gagal, coba lagi!', true);
    });
}

function removeProof() {
  proofUrl = '';
  document.getElementById('proofInput').value = '';
  document.getElementById('proofPreview').classList.add('hidden');
  document.getElementById('proofUploadArea').classList.remove('hidden');
  document.getElementById('btnSubmitOrder').classList.add('hidden');
}

function submitOrder() {
  const name = document.getElementById('custName').value.trim();
  const phone = document.getElementById('custPhone').value.trim();
  const address = document.getElementById('custAddress').value.trim();
  if (!name || !phone || !address) {
    showToast('Lengkapi data pemesan!', true);
    return;
  }
  const method = document.querySelector('input[name="payMethod"]:checked');
  const payMethod = method ? method.value : 'qris';

  if (payMethod !== 'cod' && !proofUrl) {
    showToast('Upload bukti pembayaran dulu!', true);
    return;
  }

  const btn = document.getElementById('btnSubmitOrder');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin h-5 w-5 inline mr-1" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Mengirim...';

  const items = cart.map(c => ({ id: c.id, qty: c.qty }));

  fetch(BASE_URL + '/api/submit_order.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      customer_name: name,
      customer_phone: phone,
      customer_address: address,
      payment_method: payMethod,
      proof_image: proofUrl,
      items: items
    })
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined text-base">send</span> Kirim Pesanan';

    if (!data.success) {
      showToast(data.message, true);
      return;
    }

    var custName = document.getElementById('custName').value.trim();
    cart = [];
    proofUrl = '';
    saveCart();
    closeModal('checkoutModal');
    updateBadge();
    showOrderSuccess(data.order_no, data.total, custName);
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined text-base">send</span> Kirim Pesanan';
    showToast('Gagal mengirim pesanan, coba lagi!', true);
  });
}

// ===== Modal Helpers =====
function openModal(id) {
  const m = document.getElementById(id);
  m.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
  const anyOpen = document.querySelector('#productModal:not(.hidden), #cartModal:not(.hidden), #checkoutModal:not(.hidden), #successModal:not(.hidden)');
  if (!anyOpen) document.body.style.overflow = '';
}

function showToast(msg, isError) {
  const t = document.getElementById('toast');
  const tm = document.getElementById('toastMsg');
  tm.textContent = msg;
  t.className = t.className.replace('translate-x-\\[120%\\]', '').replace('bg-green-600','').replace('bg-red-600','');
  t.classList.add(isError ? 'bg-red-600' : 'bg-green-600');
  t.style.transform = 'translateX(0)';
  setTimeout(() => { t.style.transform = 'translateX(120%)'; }, 2500);
}

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

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
  loadProducts();
  updateBadge();
});
</script>

</body>
</html>

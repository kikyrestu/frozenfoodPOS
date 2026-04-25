# 🧊 Fun Frozen Food POS System
## Panduan Instalasi Lengkap

---

## 📋 Persyaratan Sistem

- PHP 7.4+ (Rekomendasi PHP 8.x)
- MySQL 5.7+ atau MariaDB 10.x
- Web Server: Apache / Nginx (XAMPP / Laragon / WAMP)
- Browser modern (Chrome, Firefox, Edge)

---

## 🚀 Langkah Instalasi

### 1. Extract File
Extract file ZIP ini ke dalam folder web server Anda:
- **XAMPP**: `C:/xampp/htdocs/fun-frozen-food-pos/`
- **Laragon**: `C:/laragon/www/fun-frozen-food-pos/`

### 2. Buat Database
Buka **phpMyAdmin** di browser: `http://localhost/phpmyadmin`

1. Klik **"New"** / **"Baru"**
2. Buat database baru bernama: `fun_frozen_food`
3. Pilih kolasi: `utf8mb4_unicode_ci`
4. Klik **Buat**

### 3. Import SQL
1. Klik database `fun_frozen_food`
2. Klik tab **Import**
3. Pilih file `database.sql` dari folder project ini
4. Klik **Go / Jalankan**

### 4. Konfigurasi Database
Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');   // Host database
define('DB_NAME', 'fun_frozen_food'); // Nama database
define('DB_USER', 'root');        // Username MySQL
define('DB_PASS', '');            // Password MySQL (kosong jika default XAMPP)
```

### 5. Akses Aplikasi
Buka browser dan akses:
```
http://localhost/fun-frozen-food-pos/
```

---

## 🔐 Login Default

| Role  | Username | Password |
|-------|----------|----------|
| Admin | admin    | password |
| Kasir | kasir1   | password |

> ⚠️ **SEGERA GANTI PASSWORD** setelah login pertama melalui menu **Admin > Pengguna**

---

## 📁 Struktur Folder

```
fun-frozen-food-pos/
├── config/
│   ├── config.php          # Konfigurasi aplikasi
│   └── database.php        # Konfigurasi database
├── admin/
│   ├── index.php           # Dashboard admin
│   ├── products.php        # Manajemen produk
│   ├── categories.php      # Manajemen kategori
│   ├── stock.php           # Manajemen stok
│   ├── transactions.php    # Daftar transaksi
│   ├── reports.php         # Laporan penjualan
│   ├── settings.php        # Pengaturan toko
│   └── users.php           # Manajemen pengguna
├── api/
│   ├── checkout.php        # Proses checkout
│   ├── get_products.php    # Ambil data produk
│   ├── get_categories.php  # Ambil data kategori
│   └── get_transaction.php # Ambil data transaksi
├── assets/
│   ├── css/style.css       # Stylesheet utama
│   ├── js/pos.js           # JavaScript POS
│   └── icons/              # Icon PWA
├── includes/
│   ├── header.php          # Header HTML
│   ├── footer.php          # Footer HTML
│   └── sidebar.php         # Sidebar admin
├── uploads/
│   ├── products/           # Foto produk (auto-created)
│   └── logo/               # Logo toko
├── index.php               # Halaman kasir utama
├── login.php               # Halaman login
├── logout.php              # Logout
├── manifest.json           # PWA manifest
├── sw.js                   # Service Worker (PWA)
└── database.sql            # File SQL database
```

---

## 📱 Instalasi PWA (Install ke HP)

### Android (Chrome):
1. Buka aplikasi di Chrome
2. Klik ikon **3 titik** di kanan atas
3. Pilih **"Add to Home screen"** / **"Tambahkan ke Layar Utama"**
4. Klik **"Add"**

### iOS (Safari):
1. Buka aplikasi di Safari
2. Klik ikon **Share** (kotak dengan panah)
3. Pilih **"Add to Home Screen"**
4. Klik **"Add"**

---

## ⚙️ Pengaturan Toko

Setelah login sebagai admin, buka menu **Pengaturan Toko**:
- Upload logo toko
- Ubah nama dan alamat toko
- Atur pesan footer struk
- Konfigurasi pajak (jika ada)

---

## 🖨️ Fitur Cetak Struk

Setelah transaksi selesai:
1. Struk otomatis muncul
2. Klik **"Cetak"** untuk print
3. Atau close untuk menutup

---

## 🔒 Keamanan

- Semua query menggunakan **prepared statement**
- Password di-hash menggunakan **bcrypt** (password_hash)
- Validasi dan sanitasi input di semua form
- Session-based authentication
- Validasi tipe dan ukuran file upload

---

## 🛠️ Troubleshooting

### Error koneksi database:
- Pastikan MySQL berjalan di XAMPP/Laragon
- Cek username/password di `config/database.php`
- Pastikan database `fun_frozen_food` sudah dibuat

### Upload gagal:
- Pastikan folder `uploads/products/` dan `uploads/logo/` ada dan writable
- Jalankan: `chmod 755 uploads/products uploads/logo` (Linux)

### Halaman blank/error:
- Aktifkan error reporting: tambahkan `ini_set('display_errors', 1);` di atas `config/config.php`
- Cek PHP version minimal 7.4

---

## 📞 Informasi Aplikasi

- **Versi**: 1.0.0
- **Framework**: PHP Native + MySQL
- **UI**: Custom CSS + Font Awesome
- **PWA**: Service Worker + Web App Manifest

---

*© 2024 Fun Frozen Food POS System*
# frozenfoodPOS

# SESSIONS — Marketplace Jasa Web & Produk Multi-Seller

Marketplace **multi-seller** berbasis PHP native + MySQL untuk **jasa pembuatan website**
(paket Basic/Pro/Enterprise + custom brief) dan **produk** apa pun.
Dokumen rujukan: [`BRD.md`](BRD.md) · [`PRD.md`](PRD.md) · [`SRS.md`](SRS.md)

Transaksi berjalan **manual** (tanpa payment gateway): buyer memesan → transfer/QRIS →
unggah bukti → diverifikasi admin → seller memproses → selesai → review.

## Fitur

**Umum**
- Register (role `buyer`) → login → logout; password di-hash `password_hash()`
- Katalog dengan pencarian, filter kategori/tipe/harga, paginasi
- Detail listing: galeri foto, paket harga (jasa), favorit, laporan, tombol WhatsApp
- Profil (edit data + ganti password), daftar favorit, riwayat pesanan + review bintang
- Halaman statis (kontak, kredit, berita, portofolio, dll.) memakai design system yang sama

**Jasa Web**
- Paket tier otomatis: **Basic / Pro / Enterprise** (edit langsung di form listing)
- **Custom brief**: buyer membuat brief → seller mengirim penawaran harga → buyer menerima → jadi pesanan

**Multi-Seller**
- `become-seller.php`: buyer mengajukan toko → **disetujui/ditolak admin** (role sinkron otomatis tanpa re-login)
- CRUD listing dengan upload foto (validasi MIME, maks 2 MB, nama acak)
- Semua listing baru masuk **moderasi** (`pending` → admin `approve/reject`)
- `my-listings.php`, `seller-orders.php` (pesanan masuk + quote brief): proses → selesai

**Transaksi manual**
- `order-create.php` (dari listing/paket/brief) → `checkout.php?order=ID`
- Pilih metode (Transfer/QRIS, PPN 11%) → unggah bukti → **admin memverifikasi**
- Status: `menunggu_bukti` → `diverifikasi` → `proses` → `selesai` / `batal`
- Produk fisik otomatis `sold` saat pesanan selesai

**Admin** (`/admin/`, role `admin`)
- Ringkasan statistik + antrean kerja, approval seller, moderasi listing
- Verifikasi bukti bayar, kelola pengguna/kategori, laporan isi

> Di luar scope (lihat BRD): payment gateway otomatis, ekspedisi, chat real-time.

## Struktur

```
bisnis/
├── BRD.md / PRD.md / SRS.md      # Dokumen requirements (rev. visi baru)
├── index.php                      # Beranda dinamis (menggantikan index.html)
├── register/login/logout.php      # Autentikasi (throttle percobaan login)
├── dashboard.php                  # Ringkasan per role
├── listings.php / listing-detail.php / listing-form.php / my-listings.php
├── brief.php / seller-orders.php / become-seller.php
├── order-create.php / orders.php / order-detail.php / checkout.php
├── profile.php / favorites.php
├── admin/                         # Panel admin (role admin)
│   ├── _head.php / _foot.php      # Pembuka/penutup + subnav
│   └── index, sellers, listings, orders, users, categories, reports.php
├── includes/
│   ├── functions.php              # CSRF, guard role, sync_role, upload aman, helper
│   └── header.php / footer.php    # Navbar/footer (pakai $base agar bisa dari admin/)
├── assets/css/style.css           # Design system tunggal (tema terang, responsif)
├── assets/js/main.js              # Navigasi, konfirmasi, dll. (vanilla)
├── database/
│   ├── schema.sql                 # Skema 12 tabel + seed kategori
│   └── migrate.php                # Migrasi idempotent (CLI / localhost)
├── config.local.php               # ⚠️ Kredensial DB — DI-IGNORE git
├── database.php                   # ⚠️ Loader koneksi — DI-IGNORE git
├── .htaccess                      # Blokir akses langsung ke file sensitif
└── uploads/                       # Foto listing & bukti bayar — DI-IGNORE git
```

## Setup Lokal (Laragon / XAMPP)

1. Salin folder ke `www/` (Laragon) atau `htdocs/` (XAMPP).
2. Buat MySQL database `ukk_login` (atau sesuaikan).
3. Buat **`config.local.php`** (tidak ikut git):

   ```php
   <?php
   $hostname      = "localhost";
   $username      = "root";
   $password      = "";
   $database_name = "ukk_login";
   ```

4. Buat **`database.php`** di root (juga tidak ikut git — berisi koneksi saja,
   tanpa kredensial):

   ```php
   <?php
   if (file_exists(__DIR__ . '/config.local.php')) {
       require_once __DIR__ . '/config.local.php';
   } else { // fallback default Laragon
       $hostname = "localhost"; $username = "root";
       $password = ""; $database_name = "ukk_login";
   }
   $db = mysqli_connect($hostname, $username, $password, $database_name);
   if (!$db) { die("Koneksi database gagal: " . mysqli_connect_error()); }
   ```

5. Jalankan migrasi + seed (CLI, idempotent — aman dijalankan berulang):

   ```
   php database/migrate.php
   ```

   Migrasi menyiapkan 12 tabel, kategori seed, dan akun admin default.
6. Buka `http://localhost/bisnis/`.

> Tanpa `config.local.php`, `database.php` memakai fallback default Laragon
> (`root` / password kosong / DB `ukk_login`).

### Akun default

| Peran | Login | Catatan |
|---|---|---|
| Admin | `admin` / `admin123` | **Segera ganti** setelah pertama kali jalan |
| Seller | daftar → ajukan toko → disetujui admin | Role sinkron otomatis |

## Keamanan

- **Password**: selalu `password_hash()`/`password_verify()`; data lama masih plaintext
  otomatis di-hash ulang saat login pertama.
- **CSRF**: semua form POST memakai token (`csrf_check()`), logout juga POST-only.
- **XSS**: output di-escape (`e()`); **upload**: cek MIME asli (finfo), maks 2 MB,
  nama file acak, ekstensi whitelist, folder `uploads/` dilarang eksekusi script (`.htaccess`).
- **Akses**: guard `require_login()` / `require_role()` + `sync_role()` (role/status
  DB disinkronkan tiap halaman; akun nonaktif langsung keluar).
- **Login**: throttle 5 percobaan gagal / 5 menit per sesi.
- **HTTP**: header `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`;
  cookie sesi `HttpOnly` + `SameSite=Lax`.
- **.htaccess**: tolak akses langsung ke `config.local.php`, `database.php`, folder
  `database/` & `includes/`, dotfiles, dan listing direktori.
- **.gitignore**: kredensial, unggahan, dump SQL, log, dan session tidak pernah masuk git.
  Sebelum push, pastikan `git status` bersih dari `config.local.php` dan `uploads/`.

## Catatan Pengembangan

- Header POST wajib menyertakan nama tombolnya (`submit_login`, `buat_order`,
  `simpan`, `tambah`, `aksi`, dll.) — cabang pemrosesan ditentukan oleh tombol tersebut.
- Status moderasi listing: `pending/approved/rejected`; filter default moderasi admin = antrean `pending`.
- Review hanya bisa dikirim pembeli untuk pesanan berstatus `selesai` (satu per pesanan).

## Status / Roadmap

| Fase | Fokus | Status |
|---|---|---|
| 0 | Revisi BRD/PRD/SRS ke visi baru | ✅ |
| 1 | Skema DB 12 tabel + migrasi idempotent | ✅ |
| 2 | Auth, role, sync_role, CSRF, profil | ✅ |
| 3 | CRUD listing + katalog + moderasi | ✅ |
| 4 | Jasa web: paket + custom brief | ✅ |
| 5 | Transaksi manual (bukti bayar → verifikasi) | ✅ |
| 6 | Multi-seller (approval toko) | ✅ |
| 7 | Panel admin lengkap | ✅ |
| 8 | Hardening + README | ✅ |
| 9 | Payment gateway, ekspedisi, chat | ⏳ (di luar scope awal) |

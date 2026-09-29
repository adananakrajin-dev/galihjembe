# SESSIONS — Website Jual Beli Barang Bekas

Marketplace sederhana untuk jual beli barang bekas, dibangun dengan **PHP native + MySQL**.
Dokumen rujukan proyek: [`BRD.md`](BRD.md) · [`PRD.md`](PRD.md) · [`SRS.md`](SRS.md)

## Fitur (MVP)

- Registrasi, login, dan logout (session-based)
- Daftar produk, detail produk, pencarian, dan filter kategori
- Tambah / edit / hapus produk (CRUD) dengan unggah foto
- Status produk: `AVAILABLE` / `SOLD`
- Profil pengguna & daftar produk milik sendiri
- Kontak penjual
- Dashboard admin: manajemen pengguna, produk, dan moderasi

> Catatan: pembayaran online, ekspedisi, dan chat real-time **di luar scope** versi awal (BRD §8.2).

## Struktur Proyek

```
bisnis/
├── BRD.md / PRD.md / SRS.md   # Dokumen requirements
├── index.html                 # Beranda (publik)
├── register.php / login.php / logout.php
├── dashboard.php              # Dashboard user
├── checkout.php               # Halaman pembayaran (dipertahankan)
├── config.local.php           # ⚠️ Kredensial DB — DI-IGNORE git
├── database.php               # ⚠️ Loader koneksi DB — DI-IGNORE git
└── uploads/                   # Foto produk — DI-IGNORE git
```

## Setup Lokal (Laragon / XAMPP)

1. Salin folder proyek ke `www/` (Laragon) atau `htdocs/` (XAMPP).
2. Buat database MySQL bernama `ukk_login` (atau sesuaikan).
3. **Buat `config.local.php`** di root proyek (file ini tidak ikut git):

   ```php
   <?php
   $hostname      = "localhost";
   $username      = "root";
   $password      = "";
   $database_name = "ukk_login";
   ```

4. **Buat `database.php`** di root proyek (juga tidak ikut git):

   ```php
   <?php
   require_once __DIR__ . '/config.local.php';

   $db = mysqli_connect($hostname, $username, $password, $database_name);
   if (!$db) {
       die("Koneksi database gagal: " . mysqli_connect_error());
   }
   ```

5. Buka `http://localhost/bisnis/` di browser.

> Jika `config.local.php` tidak ada, `database.php` otomatis memakai fallback
> `localhost` / `root` / password kosong / DB `ukk_login` (default Laragon).

## Keamanan Data

- `.gitignore` menjaga agar **kredensial database, unggahan pengguna, dump SQL,
  log, dan file session** tidak pernah masuk ke repository.
- Password pengguna disimpan sebagai **hash** (`password_hash()` / `password_verify()`),
  bukan plaintext. Data lama yang masih plaintext otomatis di-hash ulang saat login pertama.
- Jangan pernah menaruh password di file yang ter-track git.
- Sebelum repository di-push publik, pastikan `git status` bersih dari
  `config.local.php`, `database.php`, dan folder `uploads/`.

## Roadmap

| Phase | Fokus |
|---|---|
| 1 — MVP | Auth, listing & detail produk, CRUD produk, search, kategori |
| 2 — UX | Profil, kontak penjual, status produk, favorit |
| 3 — Admin | Dashboard admin, manajemen user/produk, moderasi |
| 4 — Lanjutan | Checkout, payment gateway, chat real-time, rating & review |

Product Requirements Document (PRD)
SESSIONS — Marketplace Jasa Web & Produk

Dokumen ini menjelaskan kebutuhan produk, fitur, alur pengguna, dan spesifikasi utama untuk pengembangan SESSIONS — marketplace multi-seller tempat pengguna menjual jasa web (jasa pembuatan website) dan produk (fisik/digital) dalam satu platform.

1. Informasi Produk
Informasi	Detail
Nama Produk	SESSIONS — Marketplace Jasa Web & Produk
Platform	Web (PHP Native + MySQL)
Target Pengguna	Pelajar, mahasiswa, pekerja lepas, dan masyarakat umum
Product Type	Marketplace Multi-Seller Jasa Web & Produk
Metode Transaksi	Manual — transfer bank / QRIS statis + upload bukti, diverifikasi admin/seller
Status	Development
Dokumen	PRD v1.1
Tanggal	2026-09-29
Status Dokumen	Revisi — disesuaikan dengan visi marketplace jasa web & produk multi-seller

2. Product Overview

SESSIONS merupakan marketplace multi-seller yang memungkinkan pengguna menjual jasa pembuatan website (melalui paket siap harga maupun custom brief) dan produk fisik/digital secara bersamaan dalam satu platform.

Produk dirancang dengan antarmuka yang sederhana agar pengguna dapat menemukan listing, membandingkan paket dan harga, membuat order, mengunggah bukti pembayaran, dan menghubungi penjual dengan mudah.

Transaksi pada versi awal dilakukan secara manual (instruksi transfer bank/QRIS statis + upload bukti yang diverifikasi admin/seller) tanpa payment gateway otomatis.

3. Product Goals
Primary Goals

Memudahkan seller menjual jasa web melalui paket berjenjang maupun custom brief.

Memudahkan seller menjual produk fisik dan digital.

Memudahkan pembeli menemukan jasa web dan produk yang sesuai kebutuhan.

Menyediakan informasi listing yang lengkap dan jelas (harga, durasi, revisi, fitur).

Menyediakan alur transaksi manual yang terstruktur: order → instruksi bayar → upload bukti → verifikasi → selesai.

Menjaga kualitas platform melalui approval seller dan moderasi listing.

Secondary Goals

Menjadi wadah bagi pelajar, mahasiswa, dan freelancer untuk mulai berjualan jasa web.

Menyatukan kebutuhan jasa web dan produk dalam satu platform.

Membuat pengalaman jual beli jasa dan produk menjadi sederhana dan transparan.

4. User Personas
Persona 1 — Pembeli

Nama: Andi
Usia: 17 tahun
Status: Pelajar

Kebutuhan:

Mencari jasa pembuatan website dengan harga jelas dan terjangkau.

Melihat paket, durasi pengerjaan, dan jumlah revisi sebelum membeli.

Membeli produk yang sesuai kebutuhan dan budget.

Membayar secara aman dengan bukti transfer yang terverifikasi.

Pain Points:

Sulit menemukan penyedia jasa web yang terpercaya dan transparan harganya.

Informasi layanan sering tidak lengkap dan tidak bisa dibandingkan.

Transaksi jarak jauh rawan tidak jelas tanpa bukti pembayaran.

Persona 2 — Penjual Produk

Nama: Budi
Usia: 20 tahun
Status: Mahasiswa

Kebutuhan:

Menjual produk fisik/digital bersama seller lain dalam satu platform.

Mengunggah foto dan informasi produk.

Menentukan harga produk.

Mendapatkan calon pembeli dan menerima order.

Pain Points:

Tidak memiliki tempat khusus untuk menjual produk.

Kesulitan menjangkau calon pembeli.

Proses memasang iklan terkadang terlalu rumit.

Persona 3 — Penjual Jasa Web

Nama: Cika
Usia: 22 tahun
Status: Mahasiswi / Freelance Developer

Kebutuhan:

Menjual jasa pembuatan website melalui paket siap harga (Basic/Pro/Enterprise).

Menerima dan menjawab brief custom dari calon klien.

Mengetahui status order dan pembayaran yang masuk.

Menampilkan profil toko yang meyakinkan.

Pain Points:

Klien sering tidak memiliki brief yang jelas sehingga lingkup kerja melebar.

Pembayaran jasa web sering tidak terdokumentasi.

Belum memiliki kanal penjualan yang tersentralisasi.

5. User Roles

Website memiliki empat jenis role:

Guest

Pengguna yang belum login.

Dapat:

Melihat halaman utama.

Melihat katalog listing.

Melihat detail listing.

Mencari listing.

Melihat kategori.

Tidak dapat:

Membuat order.

Membuat listing.

Menyimpan favorit.

Mengelola profil.

Buyer (User)

Pengguna yang sudah memiliki akun dan berperan sebagai pembeli.

Dapat:

Login dan logout.

Mengelola profil.

Membuat order dan mengunggah bukti pembayaran.

Melihat status pesanan.

Menyimpan favorit.

Memberikan review dan rating setelah order selesai.

Mengirim brief custom.

Menghubungi penjual via WhatsApp.

Mengajukan diri menjadi seller.

Seller

Pengguna yang sudah disetujui admin untuk berjualan.

Dapat:

Membuat, mengubah, dan menghapus listing miliknya sendiri.

Membuat paket jasa berjenjang.

Menjawab brief dan memberi penawaran.

Melihat dan memproses order masuk.

Memverifikasi bukti pembayaran (bersama admin).

Melihat dashboard seller.

Tidak dapat:

Mengedit atau menghapus listing seller lain.

Menyetujui pengajuan seller.

Admin

Pengguna dengan akses administratif.

Dapat:

Menyetujui atau menolak pengajuan seller.

Memoderasi listing (setujui/tolak/nonaktif).

Memverifikasi bukti pembayaran.

Mengelola pengguna, order, dan kategori.

Melihat data sistem.

6. Product Features
6.1 Authentication
Description

Sistem autentikasi digunakan untuk mengelola akun pengguna dengan empat role: guest, buyer, seller, dan admin.

Features

Register.

Login.

Logout.

Session management.

Role-based access.

Register

User mengisi:

Nama.

Username.

Email.

Password.

Nomor telepon.

Acceptance Criteria

User dapat membuat akun dengan data yang valid.

Email tidak boleh digunakan oleh akun lain.

Password wajib memenuhi validasi sistem dan disimpan dalam bentuk hash.

Sistem menampilkan pesan error jika data tidak valid.

6.2 Login
Description

User dapat masuk menggunakan akun yang sudah terdaftar.

Input

Email.

Password.

Acceptance Criteria

User dapat login dengan data yang benar.

User tidak dapat login dengan password yang salah.

Sistem menampilkan pesan error ketika login gagal.

User diarahkan ke halaman yang sesuai setelah login.

6.3 Approval Seller & Profil Toko
Description

User yang ingin berjualan harus mengajukan diri menjadi seller dan menunggu persetujuan admin sebelum dapat memposting listing.

Features

Form pengajuan seller: nama toko, deskripsi toko/layanan, nomor WhatsApp, rekening bank/e-wallet.

Status pengajuan: pending, approved, rejected.

Acceptance Criteria

User yang sudah login dapat mengajukan diri menjadi seller.

Pengajuan baru berstatus menunggu approval admin.

Seller hanya dapat memposting listing setelah pengajuan disetujui.

Admin dapat menyetujui atau menolak pengajuan beserta catatan.

Seller yang ditolak dapat memperbaiki profil dan mengajukan ulang.

6.4 Listing Jasa & Produk
Description

Listing memiliki dua tipe: service (jasa pembuatan website) dan product (fisik/digital).

Acceptance Criteria

Seller dapat memilih tipe listing saat membuat listing.

Tipe service wajib memiliki kategori jasa web dan minimal satu paket atau penawaran.

Tipe product wajib memiliki foto, kondisi, dan lokasi.

Listing baru berstatus menunggu moderasi sebelum tampil publik.

6.5 Paket Jasa
Description

Seller dapat menjual jasa web melalui paket siap harga berjenjang.

Paket

Basic

Pro

Enterprise

Setiap paket memuat:

Nama paket.

Harga.

Durasi pengerjaan (hari).

Jumlah revisi.

Daftar fitur.

Acceptance Criteria

Seller dapat menambah, mengubah, dan menghapus paket pada listing jasanya.

Paket menampilkan harga, durasi, jumlah revisi, dan daftar fitur.

Buyer dapat memilih paket lalu membuat order.

Harga paket harus berupa angka positif.

6.6 Custom Brief & Penawaran
Description

Buyer dapat mengirim brief kebutuhan website kepada seller, seller memberi penawaran, dan kesepakatan (deal) menjadi order.

Acceptance Criteria

Buyer dapat mengirim brief berisi kebutuhan, budget, dan deadline.

Seller menerima notifikasi/status brief dan dapat memberi penawaran harga serta durasi.

Buyer dapat menerima (deal) atau menolak penawaran.

Saat penawaran diterima, sistem membuat order baru dari penawaran tersebut.

6.7 Order & Instruksi Pembayaran Manual
Description

Buyer membuat order dan menerima instruksi bayar secara manual tanpa payment gateway.

Acceptance Criteria

Order berhasil dibuat dari listing/paket yang dipilih.

Sistem menampilkan kode order, total tagihan, dan instruksi bayar (rekening bank / QRIS statis).

Buyer dapat mengunggah bukti pembayaran dengan format yang didukung dan ukuran maksimal 2MB.

Order berstatus menunggu_bukti setelah bukti diunggah.

Order hanya dapat dibuat oleh user yang sudah login.

6.8 Verifikasi Bukti & Status Order
Description

Admin atau seller memverifikasi bukti pembayaran dan menggerakkan status order.

Status Order

menunggu_bukti

diverifikasi

proses

selesai

batal

Acceptance Criteria

Admin/seller dapat melihat bukti pembayaran yang diunggah.

Admin/seller dapat menyetujui (diverifikasi) atau menolak bukti pembayaran.

Order yang diverifikasi berstatus lalu diproses hingga selesai.

Buyer dan seller dapat melihat status order terkini beserta riwayatnya.

Admin/seller dapat membatalkan order dengan alasan.

6.9 Review & Rating
Description

Buyer dapat memberikan review dan rating bintang setelah order selesai.

Acceptance Criteria

Review hanya dapat diberikan untuk order berstatus selesai.

Rating berupa bintang 1–5 dan komentar.

Satu order hanya dapat direview satu kali.

Review dan rating tampil pada halaman detail listing dan profil seller.

6.10 Moderasi Listing
Description

Admin dapat memoderasi seluruh listing sebelum atau setelah tampil di katalog.

Acceptance Criteria

Listing baru berstatus menunggu moderasi.

Admin dapat menyetujui, menolak, atau menonaktifkan listing.

Alasan penolakan dapat disimpan dan ditampilkan kepada seller.

Seller dapat mengedit listing yang ditolak dan mengajukan ulang.

7. Katalog Listing
Description

Halaman katalog listing menampilkan seluruh jasa web dan produk yang tersedia.

Informasi Listing

Setiap card listing menampilkan:

Foto.

Tipe listing (Jasa/Produk).

Judul listing.

Harga (atau "Mulai dari" untuk jasa dengan paket).

Kategori.

Kondisi (untuk produk).

Lokasi.

Status.

Acceptance Criteria

Listing yang tersedia dapat ditampilkan.

Listing dapat diklik untuk melihat detail.

Listing berstatus terjual atau nonaktif memiliki status yang jelas.

Tampilan dapat digunakan pada desktop dan mobile.

8. Pencarian
Description

User dapat mencari listing menggunakan kata kunci.

Search Example
Input:
"website toko online"

Result:
- Jasa Website Toko Online (Paket Pro)
- Landing Page Toko Online
- Template Website Toko Online

Acceptance Criteria

User dapat memasukkan kata kunci.

Sistem menampilkan listing yang relevan pada judul atau deskripsi.

Sistem menampilkan informasi ketika listing tidak ditemukan.

9. Kategori
Categories

Jasa Web

Landing Page

Company Profile

Toko Online

Custom App/Bot

Produk

Elektronik

Fashion

Buku

Furnitur

Peralatan Rumah Tangga

Hobi

Kendaraan

Aksesoris

Lainnya

Acceptance Criteria

User dapat memilih kategori.

Sistem menampilkan listing berdasarkan kategori.

User dapat memfilter kategori sekaligus tipe listing (jasa/produk).

User dapat kembali melihat seluruh kategori.

10. Detail Listing
Description

Halaman detail listing menampilkan informasi lengkap mengenai jasa atau produk.

Listing Information

Foto listing.

Tipe listing (Jasa/Produk).

Judul listing.

Harga.

Kategori.

Kondisi (untuk produk).

Lokasi.

Deskripsi.

Nama toko/penjual.

Rating dan review.

Status listing.

Untuk listing jasa: daftar paket (harga, durasi, revisi, fitur) dan tombol kirim brief.

Actions

User dapat:

Memilih paket dan membuat order.

Mengirim brief custom.

Menyimpan favorit.

Menghubungi penjual via WhatsApp.

Kembali ke katalog.

Acceptance Criteria

Semua informasi listing ditampilkan dengan jelas.

Daftar paket tampil lengkap untuk listing jasa.

Foto listing dapat dilihat.

Status listing ditampilkan.

Tombol order/brief hanya aktif untuk user yang sudah login.

Tombol kontak WhatsApp tersedia untuk user yang sudah login.

11. Tambah Listing
Description

Seller dapat menambahkan listing jasa web atau produk baru ke marketplace.

Form
Field	Required
Judul	✅
Tipe Listing (product/service)	✅
Foto	✅ (wajib untuk tipe product)
Harga	✅
Kategori	✅
Kondisi	✅ (khusus tipe product)
Lokasi	✅
Deskripsi	✅
Acceptance Criteria

Semua field wajib diisi sesuai tipe listing.

Harga harus berupa angka positif.

Foto harus memiliki format yang didukung, ukuran maksimal 2MB, dan disimpan dengan nama acak.

Listing berhasil disimpan setelah validasi berhasil.

Listing berstatus menunggu moderasi sebelum tampil pada katalog.

Seller yang belum disetujui admin tidak dapat membuat listing.

12. Edit Listing
Description

Seller dapat mengubah listing miliknya sendiri.

Editable Fields

Judul.

Foto.

Harga.

Kategori.

Kondisi.

Lokasi.

Deskripsi.

Status.

Paket jasa.

Acceptance Criteria

Hanya pemilik listing yang dapat mengedit listing.

Data harus divalidasi sebelum disimpan.

Perubahan berhasil disimpan ke database.

Listing yang diedit kembali melewati moderasi bila perubahan bersifat substantif.

13. Hapus Listing
Description

Seller dapat menghapus listing miliknya sendiri.

Acceptance Criteria

User hanya dapat menghapus listing miliknya sendiri.

Sistem meminta konfirmasi sebelum menghapus.

Listing tidak lagi muncul pada katalog setelah dihapus.

14. Status
14.1 Status Listing

Setiap listing memiliki status:

MENUNGGU MODERASI

Baru dibuat dan menunggu persetujuan admin.

AKTIF

Listing tampil pada katalog dan dapat dipesan.

DITOLAK

Listing ditolak admin beserta alasan.

TERJUAL

Listing sudah terjual dan tidak dapat menerima order baru.

NONAKTIF

Listing disembunyikan dari katalog oleh seller atau admin.

14.2 Status Order

Setiap order memiliki status:

MENUNGGU_BUKTI

Order dibuat, buyer belum mengunggah bukti pembayaran.

DIVERIFIKASI

Bukti pembayaran telah diverifikasi admin/seller.

PROSES

Pesanan sedang dikerjakan seller.

SELESAI

Pesanan selesai; buyer dapat memberikan review dan rating.

BATAL

Order dibatalkan oleh buyer, seller, atau admin.

15. Kontak Penjual
Description

Pembeli dapat menghubungi penjual untuk menanyakan detail layanan/produk sebelum atau sesudah membuat order.

Informasi Kontak

Nama toko/penjual.

Tombol WhatsApp yang mengarah ke nomor penjual (deep link wa.me).

Acceptance Criteria

Informasi kontak hanya ditampilkan sesuai aturan privasi sistem.

User login dapat menggunakan tombol WhatsApp untuk menghubungi penjual.

Chat real-time di dalam website belum termasuk dalam versi awal produk.

16. User Profile
User Information

Nama.

Username.

Email.

Nomor telepon.

Lokasi.

Foto profil (opsional).

Untuk seller: nama toko, deskripsi toko, status approval, rekening/e-wallet.

Features

Melihat profil.

Mengubah profil.

Melihat listing yang dimiliki.

Melihat daftar pesanan (buyer) atau order masuk (seller).

Mengajukan diri menjadi seller.

17. Admin Dashboard

Admin dashboard digunakan untuk mengelola sistem.

Features

Statistik pengguna, listing, dan order.

Approve pengajuan seller (daftar seller pending).

Verifikasi bukti pembayaran.

Moderasi listing (setujui/tolak/nonaktif).

Daftar pengguna dan hapus/nonaktifkan pengguna.

Daftar order beserta status.

Manajemen kategori.

Contoh Statistik
Total Users     : 120
Total Sellers   : 45
Total Listings  : 350
Pending Orders  : 12
Pending Sellers : 6

18. User Flow
Buyer Flow
Landing Page
      ↓
Katalog Listing
      ↓
Search / Category
      ↓
Detail Listing
      ↓
Login / Register
      ↓
Pilih Paket / Kirim Brief
      ↓
Buat Order
      ↓
Instruksi Bayar
      ↓
Upload Bukti Pembayaran
      ↓
Order Selesai
      ↓
Review & Rating

Seller Flow
Daftar & Login
  ↓
Ajukan Jadi Seller
  ↓
Disetujui Admin
  ↓
Buat Listing / Paket Jasa
  ↓
Listing Disetujui (Moderasi)
  ↓
Terima Order / Brief
  ↓
Kerjakan Pesanan
  ↓
Order Selesai

Admin Flow
Admin Login
      ↓
Admin Dashboard
      ↓
Approve Seller
      ↓
Verifikasi Pembayaran
      ↓
Moderasi Listing
      ↓
Kelola User / Order / Kategori

19. Sitemap
Website
│
├── Index (Home)
│
├── Listings (Katalog)
│   ├── Listing Detail
│   ├── Checkout / Instruksi Bayar
│   └── Order Detail
│
├── Login
│
├── Register
│
├── Profile
│
├── Brief (Buat Brief / Brief Saya)
│
├── Orders (Pesanan Saya)
│
├── Favorites
│
├── My Listings
│   ├── Listing Form (Tambah)
│   └── Listing Edit
│
├── Seller Dashboard
│
├── Contact
│
└── Admin
    ├── Dashboard
    ├── Sellers (Approval)
    ├── Listings (Moderasi)
    ├── Users
    ├── Orders
    └── Categories

20. Database Requirements
Users
users
├── id
├── name
├── username
├── email
├── password (hash)
├── phone
├── location
├── avatar
├── role (buyer/seller/admin)
├── status (active/inactive)
└── created_at

Seller Profiles
seller_profiles
├── id
├── user_id
├── store_name
├── deskripsi
├── approval (pending/approved/rejected)
├── rekening / e-wallet
└── created_at

Listings
listings
├── id
├── seller_id
├── type (product/service)
├── title
├── description
├── category_id
├── price
├── condition
├── location
├── status (aktif/terjual/nonaktif)
├── moderation (pending/approved/rejected)
└── created_at

Listing Packages
listing_packages
├── id
├── listing_id
├── name (Basic/Pro/Enterprise)
├── price
├── duration_days
├── revisions
└── features

Listing Images
listing_images
├── id
├── listing_id
├── image_url
└── is_primary

Categories
categories
├── id
├── name
└── type (jasa/produk)

Briefs
briefs
├── id
├── buyer_id
├── seller_id
├── listing_id
├── kebutuhan
├── budget
├── deadline
├── penawaran
└── status (dikirim/penawaran/deal/ditolak)

Orders
orders
├── id
├── order_code
├── buyer_id
├── seller_id
├── listing_id
├── package_id
├── total
├── payment_proof
├── status (menunggu_bukti/diverifikasi/proses/selesai/batal)
└── created_at

Reviews
reviews
├── id
├── order_id
├── buyer_id
├── seller_id
├── rating (1–5)
├── comment
└── created_at

Favorites
favorites
├── id
├── user_id
├── listing_id
└── created_at

Reports
reports
├── id
├── user_id
├── listing_id
├── reason
├── status (pending/resolved/rejected)
└── created_at

Relationship
User
  │
  │ 1:N
  ↓
Listings
  │
  ├── 1:N → Listing Packages
  ├── 1:N → Listing Images
  └── 1:N → Orders
              │
              └── 1:N → Reviews


Satu user dapat memiliki banyak listing.

Satu listing jasa dapat memiliki banyak paket.

Satu listing dapat memiliki banyak order.

Satu order hanya dapat direview satu kali.

User ↔ Listing bersifat N:N melalui favorites.

21. UI/UX Requirements
Design Principles

Website harus menggunakan desain yang:

Simple.

Clean.

Responsive.

Mudah dipahami.

Konsisten.

Listing Card

Contoh struktur:

┌─────────────────────────┐
│                         │
│       Listing Image     │
│                         │
├─────────────────────────┤
│ Jasa — Website Toko Online│
│ Mulai dari Rp 500.000    │
│ Durasi 7 hari · 2 revisi │
│ Toko Online              │
└─────────────────────────┘

┌─────────────────────────┐
│                         │
│       Produk Image      │
│                         │
├─────────────────────────┤
│ Sepatu Nike              │
│ Rp 150.000               │
│ Bekas - Baik             │
│ Fashion                  │
└─────────────────────────┘

Responsive Design

Website harus mendukung:

360px (smartphone).

768px (tablet).

1024px (laptop/desktop).

Desktop dan laptop besar.

22. Validation Requirements

Sistem harus melakukan validasi terhadap input pengguna.

Email

Format harus valid.
Contoh:
user@example.com

Harga

Harus berupa angka positif.
Contoh:
150000

Judul Listing

Tidak boleh kosong.

Deskripsi

Tidak boleh kosong.

Foto Produk

Format yang dapat digunakan:

JPG.

JPEG.

PNG.

WebP.

Ukuran maksimal: 2MB.

Nama file disimpan secara acak.

Foto wajib untuk listing tipe product.

Bukti Pembayaran

Format yang dapat digunakan: JPG, JPEG, PNG, WebP.

Ukuran maksimal: 2MB.

Nama file disimpan secara acak.

Rating

Berupa angka 1–5.

23. Error Handling

Sistem harus menampilkan pesan yang mudah dipahami ketika terjadi kesalahan.

Contoh
Login gagal
Email atau password salah.

Listing gagal ditambahkan
Silakan periksa kembali data yang dimasukkan.

Listing tidak ditemukan
Listing mungkin sudah dihapus atau tidak tersedia.

Pengajuan seller tertolak
Silakan perbaiki profil toko dan ajukan kembali.

Upload bukti gagal
Format file harus JPG/PNG/WebP dan ukuran maksimal 2MB.

Pembayaran belum diverifikasi
Order masih menunggu verifikasi bukti pembayaran.

24. Security Requirements

Password tidak disimpan dalam bentuk plain text (menggunakan hash).

Setiap form dilindungi token CSRF.

Upload file divalidasi berdasarkan MIME, ukuran maksimal 2MB, dan disimpan dengan nama acak.

Akses halaman admin harus dibatasi (role gate).

Akses halaman seller dan order harus dibatasi sesuai login.

User tidak boleh mengedit listing milik user lain.

User tidak boleh menghapus listing milik user lain.

Input pengguna harus divalidasi untuk mencegah SQL Injection dan XSS.

Sistem harus melakukan autentikasi sebelum mengakses fitur tertentu.

25. Performance Requirements

Target awal:

Halaman utama dan katalog dapat dimuat kurang dari 3 detik.

Gambar listing dan bukti pembayaran dioptimalkan sebelum ditampilkan.

Query database dibuat seefisien mungkin.

Website tetap dapat digunakan pada koneksi internet yang relatif lambat.

26. MVP Scope

Minimum Viable Product (MVP) yang harus tersedia:

Authentication

 Register

 Login

 Logout

 Profil pengguna

Katalog

 View Listings (jasa & produk)

 Listing Detail

 Pencarian

 Filter kategori

CRUD Listing (Seller)

 Tambah listing

 Edit listing

 Hapus listing

 Approval seller oleh admin

Fitur Jasa Web

 Paket jasa (Basic/Pro/Enterprise)

 Custom brief & penawaran

Transaksi Manual

 Buat order

 Instruksi bayar

 Upload bukti pembayaran

 Verifikasi pembayaran

 Status order

Lainnya

 Kontak penjual via WhatsApp

 Favorit

 Review & rating

 Dashboard buyer

 Dashboard seller

 Admin: approve seller, moderasi listing, kelola users/orders/categories

27. Future Features

Fitur yang dapat dikembangkan setelah MVP:

Payment gateway otomatis.

Checkout dan pembayaran online otomatis.

Payout otomatis ke rekening seller.

Chat real-time.

Notifikasi.

Integrasi ekspedisi.

Tracking pengiriman.

Sistem rekomendasi berbasis AI.

Aplikasi mobile.

Sistem lelang.

28. Success Metrics

Keberhasilan produk dapat diukur melalui:
Metric	Target
Registrasi pengguna	Pengguna dapat membuat akun tanpa error
Approval seller	Pengajuan seller dapat disetujui/ditolak admin
Katalog listing	Listing jasa dan produk berhasil ditampilkan
Listing creation	Seller dapat membuat listing dan paket jasa
Search & Filter	Listing dapat ditemukan berdasarkan keyword dan kategori
Custom brief	Brief terkirim dan penawaran menjadi order
Order & Pembayaran	Order dibuat, bukti terunggah, dan terverifikasi
Status order	Status order berjalan sesuai alur hingga selesai
Review & rating	Buyer dapat memberikan rating setelah order selesai
Responsiveness	Website dapat digunakan pada lebar 360/768/1024 px
Admin management	Admin dapat approve seller, moderasi listing, dan mengelola data

29. Acceptance Criteria MVP

MVP dianggap selesai apabila:

 User dapat melakukan register.

 User dapat melakukan login.

 User dapat melakukan logout.

 User dapat melihat dan mengubah profil.

 User dapat mengajukan diri menjadi seller dan menunggu approval admin.

 Seller dapat menambah, mengedit, dan menghapus listing miliknya.

 Katalog menampilkan listing jasa dan produk.

 User dapat mencari listing.

 User dapat memfilter listing berdasarkan kategori.

 User dapat melihat detail listing lengkap dengan paket jasa.

 Seller dapat membuat paket jasa berjenjang.

 Buyer dapat mengirim brief custom dan menerima penawaran.

 Buyer dapat membuat order dan melihat instruksi bayar.

 Buyer dapat mengunggah bukti pembayaran.

 Admin/seller dapat memverifikasi pembayaran dan mengubah status order.

 Buyer dapat menghubungi penjual via WhatsApp.

 User dapat menyimpan listing ke favorit.

 Buyer dapat memberikan review dan rating setelah order selesai.

 Admin dapat menyetujui pengajuan seller.

 Admin dapat memoderasi listing.

 Admin dapat mengelola pengguna, order, dan kategori.

 Website responsive pada 360px, 768px, dan 1024px.

 Validasi form berjalan.

 Hak akses user berjalan sesuai role.

30. Product Roadmap
Phase 1 — MVP Core

Fokus: Fondasi marketplace.

Authentication (register, login, logout, profil).

Approval seller.

Katalog listing (jasa & produk).

Listing detail.

CRUD listing.

Search & kategori.

Phase 2 — Fitur Jasa Web

Fokus: Menjual jasa web.

Paket jasa.

Custom brief & penawaran.

Kontak WhatsApp.

Favorit.

Phase 3 — Transaksi Manual

Fokus: Mendukung proses transaksi.

Buat order.

Instruksi bayar.

Upload bukti pembayaran.

Verifikasi pembayaran & status order.

Review & rating.

Phase 4 — Admin & Dashboard

Fokus: Pengelolaan sistem.

Dashboard buyer, seller, admin.

Moderasi listing.

Manajemen user, order, kategori.

Fase berikutnya (di luar MVP): payment gateway, chat real-time, notifikasi, ekspedisi & tracking, rekomendasi AI, mobile app.

31. Out of Scope

Fitur berikut tidak termasuk dalam versi MVP:

Payment gateway otomatis.

Ekspedisi dan tracking otomatis.

Chat real-time.

Rekomendasi berbasis AI.

Aplikasi mobile.

Sistem lelang (auction).

32. Conclusion

SESSIONS — Marketplace Jasa Web & Produk dirancang sebagai marketplace multi-seller sederhana yang memungkinkan pengguna menjual dan membeli jasa pembuatan website serta produk fisik/digital dalam satu platform.

Fokus utama versi pertama adalah menyediakan fungsi marketplace dasar: autentikasi, approval seller, katalog dan pencarian, CRUD listing, paket jasa, custom brief, transaksi manual dengan instruksi bayar dan upload bukti, status order, kontak via WhatsApp, favorit, review dan rating, serta pengelolaan oleh admin.

Pengembangan selanjutnya dapat dilakukan secara bertahap dengan menambahkan payment gateway otomatis, komunikasi real-time, notifikasi, ekspedisi, dan fitur lanjutan lainnya.

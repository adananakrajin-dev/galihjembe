Product Requirements Document (PRD)
Website Jual Beli Barang Bekas

Dokumen ini menjelaskan kebutuhan produk, fitur, alur pengguna, dan spesifikasi utama untuk pengembangan website jual beli barang bekas.

1. Informasi Produk
Informasi	Detail
Nama Produk	Website Jual Beli Barang Bekas
Platform	Web
Target Pengguna	Pelajar, mahasiswa, dan masyarakat umum
Product Type	Marketplace Barang Bekas
Status	Development
Dokumen	PRD v1.0
2. Product Overview

Website ini merupakan platform jual beli barang bekas yang memungkinkan pengguna untuk menjual barang yang sudah tidak digunakan dan mencari barang bekas yang sesuai dengan kebutuhan.

Produk dirancang dengan antarmuka yang sederhana agar pengguna dapat menemukan barang, melihat detail produk, dan menghubungi penjual dengan mudah.

3. Product Goals
Primary Goals

Memudahkan pengguna menjual barang bekas.

Memudahkan pengguna menemukan barang bekas.

Menyediakan informasi produk yang lengkap dan jelas.

Menyediakan komunikasi antara pembeli dan penjual.

Membuat pengalaman jual beli barang bekas menjadi sederhana.

Secondary Goals

Membantu mengurangi barang yang tidak terpakai.

Mendorong penggunaan kembali barang yang masih layak.

Menjadi platform sederhana untuk transaksi barang bekas.

4. User Personas
Persona 1 — Pembeli

Nama: Andi
Usia: 17 tahun
Status: Pelajar

Kebutuhan:

Mencari barang dengan harga terjangkau.

Melihat kondisi barang sebelum membeli.

Mengetahui informasi penjual.

Menghubungi penjual dengan mudah.

Pain Points:

Sulit menemukan barang bekas yang sesuai.

Informasi kondisi barang terkadang tidak lengkap.

Harga barang bekas sulit dibandingkan.

Persona 2 — Penjual

Nama: Budi
Usia: 20 tahun
Status: Mahasiswa

Kebutuhan:

Menjual barang yang sudah tidak digunakan.

Mengunggah foto dan informasi barang.

Menentukan harga barang.

Mendapatkan calon pembeli.

Pain Points:

Tidak memiliki tempat khusus untuk menjual barang.

Kesulitan menjangkau calon pembeli.

Proses memasang iklan terkadang terlalu rumit.

5. User Roles

Website memiliki tiga jenis role:

Guest

Pengguna yang belum login.

Dapat:

Melihat halaman utama.

Melihat daftar produk.

Melihat detail produk.

Mencari produk.

Melihat kategori.

Tidak dapat:

Menambahkan produk.

Mengelola produk.

Menghubungi penjual melalui fitur pengguna.

User

Pengguna yang sudah memiliki akun.

Dapat:

Login.

Mengelola profil.

Menambahkan produk.

Mengedit produk sendiri.

Menghapus produk sendiri.

Mencari produk.

Melihat detail produk.

Menghubungi penjual.

Admin

Pengguna dengan akses administratif.

Dapat:

Mengelola pengguna.

Mengelola seluruh produk.

Menghapus produk.

Melakukan moderasi konten.

Melihat data sistem.

6. Product Features
6.1 Authentication
Description

Sistem autentikasi digunakan untuk mengelola akun pengguna.

Features

Register.

Login.

Logout.

Session management.

Role-based access.

Register

User mengisi:

Nama.

Email.

Password.

Nomor telepon.

Acceptance Criteria

User dapat membuat akun dengan data yang valid.

Email tidak boleh digunakan oleh akun lain.

Password wajib memenuhi validasi sistem.

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

7. Product Listing
Description

Halaman product listing menampilkan seluruh barang yang tersedia.

Informasi Produk

Setiap card produk menampilkan:

Foto.

Nama produk.

Harga.

Kondisi.

Kategori.

Status.

Acceptance Criteria

Produk yang tersedia dapat ditampilkan.

Produk dapat diklik untuk melihat detail.

Produk yang sudah terjual memiliki status yang jelas.

Tampilan dapat digunakan pada desktop dan mobile.

8. Product Search
Description

User dapat mencari produk menggunakan kata kunci.

Search Example
Input:
"sepatu"

Result:
- Sepatu Nike
- Sepatu Adidas
- Sepatu Converse

Acceptance Criteria

User dapat memasukkan kata kunci.

Sistem menampilkan produk yang relevan.

Sistem menampilkan informasi ketika produk tidak ditemukan.

9. Product Category
Categories

Elektronik

Fashion

Buku

Furniture

Peralatan Rumah Tangga

Hobi

Kendaraan

Aksesoris

Lainnya

Acceptance Criteria

User dapat memilih kategori.

Sistem menampilkan produk berdasarkan kategori.

User dapat kembali melihat seluruh kategori.

10. Product Detail
Description

Halaman detail produk menampilkan informasi lengkap mengenai barang.

Product Information

Foto produk.

Nama produk.

Harga.

Kondisi.

Kategori.

Deskripsi.

Nama penjual.

Lokasi penjual.

Status produk.

Actions

User dapat:

Menghubungi penjual.

Kembali ke daftar produk.

Acceptance Criteria

Semua informasi produk ditampilkan dengan jelas.

Foto produk dapat dilihat.

Status produk ditampilkan.

Tombol kontak penjual tersedia untuk user yang sudah login.

11. Add Product
Description

Penjual dapat menambahkan barang baru ke marketplace.

Form
Field	Required
Nama Produk	✅
Foto	✅
Harga	✅
Kategori	✅
Kondisi	✅
Deskripsi	✅
Acceptance Criteria

Semua field wajib diisi.

Harga harus berupa angka.

Foto harus memiliki format yang didukung.

Produk berhasil disimpan setelah validasi berhasil.

Produk muncul pada daftar produk setelah berhasil dibuat.

12. Edit Product
Description

Penjual dapat mengubah informasi produk miliknya.

Editable Fields

Nama.

Foto.

Harga.

Kategori.

Kondisi.

Deskripsi.

Status.

Acceptance Criteria

Hanya pemilik produk yang dapat mengedit produk.

Data harus divalidasi sebelum disimpan.

Perubahan berhasil disimpan ke database.

13. Delete Product
Description

Penjual dapat menghapus produk miliknya.

Acceptance Criteria

User hanya dapat menghapus produk miliknya sendiri.

Sistem meminta konfirmasi sebelum menghapus.

Produk tidak lagi muncul pada daftar produk setelah dihapus.

14. Product Status

Setiap produk memiliki status:

AVAILABLE
SOLD

AVAILABLE

Barang masih tersedia untuk dibeli.

SOLD

Barang sudah terjual dan tidak dapat ditawarkan kembali.

15. Contact Seller
Description

Pembeli dapat menghubungi penjual untuk menanyakan barang dan melakukan kesepakatan transaksi.

Informasi Kontak

Nama penjual.

Nomor telepon atau kontak yang tersedia.

Acceptance Criteria

Informasi kontak hanya ditampilkan sesuai aturan privasi sistem.

User dapat menggunakan informasi tersebut untuk menghubungi penjual.

Catatan: Sistem pembayaran dan transaksi belum termasuk dalam versi awal produk.

16. User Profile
User Information

Nama.

Email.

Nomor telepon.

Alamat.

Foto profil (opsional).

Features

Melihat profil.

Mengubah profil.

Melihat produk yang dimiliki.

17. Admin Dashboard

Admin dashboard digunakan untuk mengelola sistem.

Features

Statistik pengguna.

Statistik produk.

Daftar pengguna.

Daftar produk.

Hapus pengguna.

Hapus produk.

Moderasi produk.

Contoh Statistik
Total Users     : 120
Total Products  : 350
Available       : 280
Sold            : 70

18. User Flow
Buyer Flow
Landing Page
     ↓
Browse Products
     ↓
Search / Category
     ↓
Product Detail
     ↓
Login / Register
     ↓
Contact Seller
     ↓
Transaction

Seller Flow
Login
  ↓
Dashboard
  ↓
Add Product
  ↓
Fill Product Form
  ↓
Submit
  ↓
Product Published
  ↓
Receive Buyer Contact

Admin Flow
Admin Login
     ↓
Admin Dashboard
     ↓
Manage Users / Products
     ↓
Moderation
     ↓
Update / Delete Data

19. Sitemap
Website
│
├── Home
│
├── Products
│   ├── Product Detail
│   └── Category
│
├── Login
│
├── Register
│
├── Profile
│
├── My Products
│   ├── Add Product
│   └── Edit Product
│
└── Admin
    ├── Dashboard
    ├── Users
    └── Products

20. Database Requirements
Users
users
├── id
├── name
├── email
├── password
├── phone
├── address
├── role
└── created_at

Products
products
├── id
├── seller_id
├── name
├── description
├── category
├── condition
├── price
├── image
├── status
└── created_at

Relationship
User
  │
  │ 1:N
  ↓
Products


Satu user dapat memiliki banyak produk.

21. UI/UX Requirements
Design Principles

Website harus menggunakan desain yang:

Simple.

Clean.

Responsive.

Mudah dipahami.

Konsisten.

Product Card

Contoh struktur:

┌─────────────────────────┐
│                         │
│       Product Image     │
│                         │
├─────────────────────────┤
│ Nama Produk             │
│ Rp 150.000              │
│ Bekas - Baik             │
│ Fashion                 │
└─────────────────────────┘

Responsive Design

Website harus mendukung:

Desktop.

Laptop.

Tablet.

Smartphone.

22. Validation Requirements

Sistem harus melakukan validasi terhadap input pengguna.

Email
Format harus valid.
Contoh:
user@example.com

Harga
Harus berupa angka.
Contoh:
150000

Nama Produk
Tidak boleh kosong.

Deskripsi
Tidak boleh kosong.

Foto

Format yang dapat digunakan:

JPG.

JPEG.

PNG.

WebP.

23. Error Handling

Sistem harus menampilkan pesan yang mudah dipahami ketika terjadi kesalahan.

Contoh
Login gagal
Email atau password salah.

Produk gagal ditambahkan
Silakan periksa kembali data yang dimasukkan.

Produk tidak ditemukan
Produk mungkin sudah dihapus atau tidak tersedia.

24. Security Requirements

Password tidak disimpan dalam bentuk plain text.

Akses halaman admin harus dibatasi.

User tidak boleh mengedit produk milik user lain.

User tidak boleh menghapus produk milik user lain.

Input pengguna harus divalidasi.

Sistem harus melakukan autentikasi sebelum mengakses fitur tertentu.

25. Performance Requirements

Target awal:

Halaman utama dapat dimuat dengan cepat.

Gambar produk dioptimalkan sebelum ditampilkan.

Query database dibuat seefisien mungkin.

Website tetap dapat digunakan pada koneksi internet yang relatif lambat.

26. MVP Scope

Minimum Viable Product (MVP) yang harus tersedia:

Authentication

 Register

 Login

 Logout

Product

 View Products

 Product Detail

 Add Product

 Edit Product

 Delete Product

Discovery

 Search

 Category

User

 Profile

 My Products

Admin

 User Management

 Product Management

27. Future Features

Fitur yang dapat dikembangkan setelah MVP:

Chat real-time.

Rating dan review.

Wishlist.

Notifikasi.

Payment gateway.

Sistem checkout.

Integrasi ekspedisi.

Tracking pengiriman.

Sistem rekomendasi produk.

Aplikasi mobile.

28. Success Metrics

Keberhasilan produk dapat diukur melalui:

Metric	Target
Registrasi pengguna	Pengguna dapat membuat akun tanpa error
Product Listing	Produk berhasil ditampilkan
Product Creation	Penjual dapat membuat produk
Search	Produk dapat ditemukan berdasarkan keyword
Product Detail	Informasi produk tampil lengkap
Responsiveness	Website dapat digunakan pada berbagai ukuran layar
Admin Management	Admin dapat mengelola data pengguna dan produk
29. Acceptance Criteria MVP

MVP dianggap selesai apabila:

 User dapat melakukan register.

 User dapat melakukan login.

 User dapat melakukan logout.

 User dapat melihat produk.

 User dapat mencari produk.

 User dapat memfilter produk berdasarkan kategori.

 User dapat melihat detail produk.

 User dapat menambahkan produk.

 User dapat mengedit produk miliknya.

 User dapat menghapus produk miliknya.

 User dapat menghubungi penjual.

 Admin dapat mengelola produk.

 Admin dapat mengelola pengguna.

 Website responsive.

 Validasi form berjalan.

 Hak akses user berjalan sesuai role.

30. Product Roadmap
Phase 1 — MVP

Fokus: Fitur utama marketplace.

Authentication.

Product listing.

Product detail.

Add/Edit/Delete product.

Search.

Category.

Phase 2 — User Experience

Fokus: Meningkatkan pengalaman pengguna.

Profile.

Contact seller.

Product status.

Wishlist.

Rating & review.

Phase 3 — Transaction

Fokus: Mendukung proses transaksi.

Checkout.

Payment gateway.

Shipping integration.

Order management.

Tracking.

Phase 4 — Advanced

Fokus: Pengembangan fitur lanjutan.

Real-time chat.

Recommendation system.

Notification.

Mobile application.

31. Out of Scope

Fitur berikut tidak termasuk dalam versi MVP:

Payment gateway.

Automatic shipping.

Delivery tracking.

Real-time chat.

Mobile application.

AI recommendation.

Auction system.

32. Conclusion

Website Jual Beli Barang Bekas dirancang sebagai marketplace sederhana yang memungkinkan pengguna untuk mencari, menjual, dan menemukan barang bekas dengan mudah.

Fokus utama versi pertama adalah menyediakan fungsi marketplace dasar seperti autentikasi, manajemen produk, pencarian, kategori, detail produk, dan pengelolaan oleh admin.

Pengembangan selanjutnya dapat dilakukan secara bertahap dengan menambahkan fitur transaksi, komunikasi, rating, pembayaran, dan pengiriman.
Software Requirements Specification (SRS)
Website Marketplace Jual Beli Barang

Versi: 1.0
Status: Draft
Nama Proyek: Marketplace Website
Dokumen: Software Requirements Specification (SRS)

1. Pendahuluan
1.1 Tujuan

Dokumen ini menjelaskan kebutuhan perangkat lunak untuk membangun sebuah website marketplace yang memungkinkan pengguna untuk menjual dan membeli barang secara online.

Website ini terinspirasi dari konsep marketplace seperti OLX, di mana pengguna dapat membuat iklan barang, mencari barang berdasarkan kategori atau kata kunci, melihat detail barang, serta menghubungi penjual.

Sistem dibuat sebagai proyek sekolah dengan fokus pada fungsi marketplace dasar yang mudah digunakan dan dapat dikembangkan lebih lanjut.

1.2 Ruang Lingkup

Sistem akan menyediakan fitur:

Registrasi dan login pengguna.

Pengelolaan profil pengguna.

Membuat iklan barang.

Mengedit dan menghapus iklan.

Mencari barang.

Filter dan kategori barang.

Melihat detail barang.

Menghubungi penjual.

Menyimpan barang favorit.

Melaporkan iklan.

Dashboard pengguna.

Dashboard administrator.

Moderasi iklan oleh administrator.

Sistem pada versi awal tidak mencakup pembayaran online dan sistem pengiriman otomatis. Transaksi dilakukan secara langsung antara pembeli dan penjual.

1.3 Tujuan Sistem

Sistem dibuat untuk:

Mempermudah pengguna menjual barang.

Mempermudah pengguna menemukan barang yang ingin dibeli.

Menyediakan tempat untuk menampilkan informasi barang secara terstruktur.

Memungkinkan pembeli dan penjual berkomunikasi.

Menyediakan sistem pengelolaan iklan bagi administrator.

2. Deskripsi Umum Sistem
2.1 Perspektif Produk

Website merupakan aplikasi berbasis web yang dapat diakses menggunakan browser pada komputer maupun perangkat mobile.

Secara umum sistem memiliki tiga jenis pengguna:

Guest — pengguna yang belum login.

User — pengguna yang sudah memiliki akun.

Admin — pengguna yang bertugas mengelola dan memoderasi sistem.

2.2 Karakteristik Pengguna
Guest

Guest dapat:

Melihat halaman utama.

Melihat daftar barang.

Mencari barang.

Melihat detail barang.

Melihat kategori.

Guest tidak dapat:

Membuat iklan.

Mengirim pesan kepada penjual.

Menambahkan barang ke favorit.

Mengelola profil.

User

User dapat:

Login dan logout.

Mengelola profil.

Membuat iklan.

Mengedit iklan.

Menghapus iklan.

Melihat iklan miliknya.

Mencari barang.

Menambahkan barang ke favorit.

Menghubungi penjual.

Melaporkan iklan.

Admin

Admin dapat:

Login ke dashboard admin.

Melihat seluruh pengguna.

Melihat seluruh iklan.

Menghapus iklan yang melanggar aturan.

Menonaktifkan akun pengguna.

Mengelola kategori.

Melihat laporan dari pengguna.

3. Functional Requirements
FR-01 — Registrasi

Sistem harus menyediakan fitur registrasi akun.

Data minimal:

Nama pengguna.

Email.

Password.

Nomor telepon (opsional).

Ketentuan:

Email harus memiliki format yang valid.

Email tidak boleh digunakan oleh akun lain.

Password harus memenuhi aturan keamanan minimum.

Sistem harus memberikan pesan ketika registrasi berhasil atau gagal.

FR-02 — Login

Sistem harus memungkinkan pengguna masuk menggunakan:

Email.

Password.

Jika data login benar, pengguna diarahkan ke halaman utama atau dashboard.

Jika data salah, sistem menampilkan pesan kesalahan.

FR-03 — Logout

User dan Admin dapat keluar dari akun.

Setelah logout, session/token autentikasi harus dihapus atau dibuat tidak berlaku.

FR-04 — Profil Pengguna

User dapat melihat dan mengubah:

Nama.

Foto profil.

Nomor telepon.

Lokasi.

Deskripsi singkat.

Email akun tidak dapat diubah sembarangan atau harus melalui mekanisme verifikasi jika fitur tersebut tersedia.

FR-05 — Membuat Iklan

User dapat membuat iklan barang.

Data iklan minimal:

Judul barang.

Deskripsi.

Harga.

Kategori.

Kondisi barang.

Lokasi.

Foto barang.

Status barang.

Contoh kondisi:

Baru.

Bekas.

Seperti baru.

Setelah dibuat, iklan dapat berstatus:

Menunggu moderasi.

Aktif.

Ditolak.

Terjual.

Nonaktif.

FR-06 — Mengubah Iklan

Pemilik iklan dapat mengubah informasi iklannya.

Informasi yang dapat diubah meliputi:

Judul.

Deskripsi.

Harga.

Kategori.

Kondisi.

Lokasi.

Foto.

FR-07 — Menghapus Iklan

Pemilik iklan dapat menghapus atau menonaktifkan iklannya.

Iklan yang sudah dihapus tidak ditampilkan kepada pengguna umum.

FR-08 — Daftar Produk

Sistem menampilkan daftar barang yang tersedia.

Setiap kartu barang minimal menampilkan:

Foto.

Nama barang.

Harga.

Lokasi.

Kondisi.

FR-09 — Detail Produk

Sistem harus menyediakan halaman detail barang.

Informasi yang ditampilkan:

Foto barang.

Judul.

Harga.

Deskripsi.

Kondisi.

Lokasi.

Nama penjual.

Tanggal iklan dibuat.

Tombol hubungi penjual.

Tombol favorit.

Tombol laporkan.

FR-10 — Pencarian

User dapat mencari barang berdasarkan kata kunci.

Contoh:

"Laptop Lenovo"

Sistem menampilkan iklan yang memiliki kata kunci yang relevan pada judul atau deskripsi.

FR-11 — Filter

User dapat melakukan filter berdasarkan:

Kategori.

Harga minimum.

Harga maksimum.

Kondisi.

Lokasi.

FR-12 — Sorting

User dapat mengurutkan hasil berdasarkan:

Terbaru.

Harga termurah.

Harga termahal.

FR-13 — Kategori

Sistem menyediakan kategori barang.

Contoh:

Elektronik.

Kendaraan.

Fashion.

Rumah Tangga.

Buku.

Olahraga.

Lainnya.

Admin dapat menambah, mengubah, atau menghapus kategori.

FR-14 — Favorit

User yang login dapat menyimpan barang ke daftar favorit.

User dapat:

Menambahkan barang ke favorit.

Menghapus barang dari favorit.

Melihat daftar barang favorit.

FR-15 — Kontak Penjual

Pembeli dapat menghubungi penjual melalui sistem.

Minimal tersedia:

Tombol WhatsApp atau nomor telepon yang disediakan penjual.

Jika sistem memiliki fitur chat internal, user juga dapat mengirim pesan kepada penjual melalui website.

FR-16 — Laporan Iklan

User dapat melaporkan iklan yang dianggap melanggar aturan.

Alasan laporan dapat berupa:

Penipuan.

Barang ilegal.

Informasi palsu.

Spam.

Konten tidak pantas.

Alasan lainnya.

FR-17 — Dashboard User

User memiliki dashboard yang menampilkan:

Iklan saya.

Favorit.

Profil.

Status iklan.

User dapat melihat status setiap iklan:

Menunggu moderasi.

Aktif.

Ditolak.

Terjual.

Nonaktif.

FR-18 — Dashboard Admin

Admin memiliki dashboard untuk:

Melihat jumlah user.

Melihat jumlah iklan.

Melihat laporan.

Melihat iklan terbaru.

Mengelola user.

Mengelola kategori.

Memoderasi iklan.

FR-19 — Moderasi Iklan

Admin dapat:

Menyetujui iklan.

Menolak iklan.

Menonaktifkan iklan.

Melihat alasan laporan.

Jika iklan ditolak, sistem dapat menyimpan alasan penolakan.

FR-20 — Manajemen Pengguna

Admin dapat melihat daftar pengguna.

Admin dapat:

Melihat profil pengguna.

Menonaktifkan akun.

Mengaktifkan kembali akun.

Melihat iklan milik pengguna.

4. Non-Functional Requirements
NFR-01 — Performance

Halaman utama sebaiknya dapat dimuat dalam waktu kurang dari 3 detik pada koneksi internet yang normal.

Pencarian harus memberikan hasil dalam waktu yang wajar.

Gambar harus dikompresi agar tidak terlalu membebani server.

NFR-02 — Security

Password harus disimpan dalam bentuk hash, bukan plaintext.

Sistem harus menggunakan autentikasi untuk halaman yang membutuhkan login.

User hanya boleh mengubah atau menghapus iklan miliknya sendiri.

Admin memiliki hak akses khusus.

Input pengguna harus divalidasi untuk mencegah serangan seperti SQL Injection dan XSS.

NFR-03 — Usability

Website harus:

Mudah digunakan oleh pengguna baru.

Memiliki navigasi yang jelas.

Responsive pada desktop dan mobile.

Menggunakan bahasa yang mudah dipahami.

NFR-04 — Availability

Sistem diharapkan dapat digunakan selama server aktif dan memiliki koneksi internet.

NFR-05 — Maintainability

Kode program harus:

Terstruktur.

Menggunakan penamaan yang konsisten.

Memisahkan frontend, backend, dan database jika memungkinkan.

Memiliki dokumentasi untuk bagian penting sistem.

NFR-06 — Compatibility

Website dapat digunakan pada browser modern seperti:

Google Chrome.

Mozilla Firefox.

Microsoft Edge.

Safari.

5. Use Case
5.1 Aktor
Aktor	Deskripsi
Guest	Pengunjung yang belum login
User	Pengguna yang sudah login
Admin	Pengelola sistem
5.2 Use Case Utama
ID	Use Case	Aktor
UC-01	Registrasi	Guest
UC-02	Login	User/Admin
UC-03	Logout	User/Admin
UC-04	Melihat produk	Guest/User
UC-05	Mencari produk	Guest/User
UC-06	Filter produk	Guest/User
UC-07	Melihat detail produk	Guest/User
UC-08	Membuat iklan	User
UC-09	Mengedit iklan	User
UC-10	Menghapus iklan	User
UC-11	Menambahkan favorit	User
UC-12	Menghubungi penjual	User
UC-13	Melaporkan iklan	User
UC-14	Mengelola profil	User
UC-15	Mengelola iklan	Admin
UC-16	Mengelola user	Admin
UC-17	Mengelola kategori	Admin
UC-18	Memproses laporan	Admin
6. Database Requirements

Database minimal terdiri dari tabel berikut:

Users
Field	Tipe	Keterangan
id	Integer	Primary key
name	String	Nama user
email	String	Email
password	String	Password yang telah di-hash
phone	String	Nomor telepon
location	String	Lokasi
avatar	String	Foto profil
role	Enum	user/admin
status	Enum	active/inactive
created_at	DateTime	Waktu dibuat
updated_at	DateTime	Waktu diperbarui
Products
Field	Tipe	Keterangan
id	Integer	Primary key
user_id	Integer	Pemilik iklan
category_id	Integer	Kategori
title	String	Judul
description	Text	Deskripsi
price	Decimal	Harga
condition	Enum	Kondisi barang
location	String	Lokasi
status	Enum	Status iklan
created_at	DateTime	Waktu dibuat
updated_at	DateTime	Waktu diperbarui
Product Images
Field	Tipe	Keterangan
id	Integer	Primary key
product_id	Integer	ID barang
image_url	String	Lokasi gambar
is_primary	Boolean	Gambar utama
Categories
Field	Tipe	Keterangan
id	Integer	Primary key
name	String	Nama kategori
created_at	DateTime	Waktu dibuat
Favorites
Field	Tipe	Keterangan
id	Integer	Primary key
user_id	Integer	ID user
product_id	Integer	ID barang
created_at	DateTime	Waktu dibuat
Reports
Field	Tipe	Keterangan
id	Integer	Primary key
user_id	Integer	Pelapor
product_id	Integer	Barang yang dilaporkan
reason	String	Alasan laporan
status	Enum	pending/resolved/rejected
created_at	DateTime	Waktu dibuat
7. Business Rules

Satu email hanya dapat digunakan oleh satu akun.

User hanya dapat mengedit dan menghapus iklannya sendiri.

Barang yang berstatus terjual tidak boleh menerima transaksi baru.

Iklan harus melewati proses moderasi jika fitur moderasi diaktifkan.

User yang tidak login tidak dapat membuat iklan.

User yang tidak login tidak dapat menggunakan fitur favorit.

Admin dapat menonaktifkan iklan yang melanggar aturan.

Admin dapat menonaktifkan akun pengguna.

Harga barang harus berupa angka positif.

Iklan harus memiliki minimal satu foto.

8. System Architecture

Sistem menggunakan arsitektur client-server.

+----------------------+
|      User Browser    |
|  Desktop / Mobile    |
+----------+-----------+
           |
           | HTTP / HTTPS
           v
+----------------------+
|       Frontend       |
| UI / Pages / Forms   |
+----------+-----------+
           |
           | API
           v
+----------------------+
|       Backend        |
| Auth / Business Logic|
+----------+-----------+
           |
           v
+----------------------+
|       Database       |
| Users / Products /   |
| Categories / Reports |
+----------------------+


Teknologi yang dapat digunakan:

Frontend

HTML.

CSS.

JavaScript.

React/Vue (opsional).

Backend

Salah satu:

Node.js + Express.

Laravel.

Django.

PHP Native.

Database

Salah satu:

MySQL.

PostgreSQL.

MongoDB.

9. User Interface Requirements
Halaman Utama

Halaman utama minimal memiliki:

Logo website.

Search bar.

Tombol login/register.

Kategori.

Daftar barang terbaru.

Tombol jual barang.

Halaman Detail Barang

Menampilkan:

Foto.

Nama barang.

Harga.

Kondisi.

Lokasi.

Deskripsi.

Informasi penjual.

Tombol hubungi penjual.

Tombol favorit.

Tombol laporan.

Dashboard User

Menampilkan:

Profil.

Daftar iklan.

Status iklan.

Favorit.

Tombol tambah iklan.

Dashboard Admin

Menampilkan:

Statistik website.

Daftar user.

Daftar iklan.

Daftar laporan.

Manajemen kategori.

10. Acceptance Criteria

Sistem dianggap memenuhi kebutuhan apabila:

User dapat membuat akun.

User dapat login dan logout.

User dapat membuat iklan.

User dapat mengunggah foto barang.

User dapat mengedit dan menghapus iklan miliknya.

Pengunjung dapat mencari barang.

Pengunjung dapat menggunakan filter.

Pengunjung dapat melihat detail barang.

User dapat menyimpan barang ke favorit.

User dapat menghubungi penjual.

User dapat melaporkan iklan.

Admin dapat mengelola user.

Admin dapat mengelola kategori.

Admin dapat memoderasi iklan.

Website dapat digunakan pada desktop dan mobile.

11. Future Development

Fitur berikut dapat dikembangkan pada versi berikutnya:

Chat real-time.

Sistem pembayaran online.

Sistem checkout.

Integrasi jasa pengiriman.

Rating dan review penjual.

Notifikasi email.

Push notification.

Verifikasi identitas pengguna.

Rekomendasi barang.

Sistem bidding/nego harga.

Aplikasi Android/iOS.

12. Kesimpulan

Website marketplace ini dirancang untuk menyediakan platform jual beli barang yang sederhana dan mudah digunakan. Sistem memungkinkan pengguna membuat dan mencari iklan barang serta berkomunikasi dengan penjual.

Untuk tahap awal proyek sekolah, fokus utama adalah autentikasi pengguna, CRUD iklan, pencarian, kategori, favorit, laporan, dan dashboard admin. Fitur seperti pembayaran online, pengiriman, dan chat real-time dapat dikembangkan pada tahap selanjutnya.
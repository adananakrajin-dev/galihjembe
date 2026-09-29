Software Requirements Specification (SRS)
SESSIONS — Marketplace Jasa Web & Produk

Versi: 1.1
Status: Revisi — disesuaikan dengan visi marketplace jasa web & produk multi-seller
Nama Proyek: SESSIONS — Marketplace Jasa Web & Produk
Dokumen: Software Requirements Specification (SRS)

1. Pendahuluan
1.1 Tujuan

Dokumen ini menjelaskan kebutuhan perangkat lunak untuk membangun SESSIONS, sebuah website marketplace multi-seller yang memungkinkan pengguna menjual jasa pembuatan website (jasa web) dan produk (fisik/digital) dalam satu platform.

Website ini terinspirasi dari konsep marketplace multi-seller, di mana pengguna dapat membuat listing jasa atau produk, mencari listing berdasarkan kategori atau kata kunci, melihat detail listing, membuat order, mengunggah bukti pembayaran, serta menghubungi penjual.

Sistem dibangun menggunakan PHP native dan MySQL sebagai proyek sekolah/kuliah dengan fokus pada MVP (Minimum Viable Product) yang mudah digunakan dan dapat dikembangkan lebih lanjut.

1.2 Ruang Lingkup

Sistem akan menyediakan fitur:

Registrasi, login, dan logout pengguna.

Pengelolaan profil pengguna.

Pengajuan menjadi seller dan approval seller oleh admin.

Katalog listing jasa web dan produk.

Pencarian dan filter kategori.

Detail listing.

CRUD listing khusus seller (tambah, edit, hapus).

Paket jasa berjenjang (Basic/Pro/Enterprise).

Custom brief: buyer kirim brief → seller beri penawaran → deal menjadi order.

Order manual dengan instruksi bayar (transfer bank/QRIS statis) dan upload bukti pembayaran.

Status order serta verifikasi pembayaran oleh admin/seller.

Kontak penjual via WhatsApp.

Menyimpan listing favorit.

Review dan rating bintang setelah order selesai.

Melaporkan listing.

Dashboard buyer, seller, dan admin.

Moderasi listing oleh administrator.

Manajemen pengguna, order, dan kategori oleh administrator.

Sistem pada versi awal tidak mencakup payment gateway otomatis, ekspedisi/tracking otomatis, chat real-time, rekomendasi AI, aplikasi mobile, dan sistem lelang. Transaksi dilakukan secara manual: buyer membuat order, menerima instruksi bayar, mengunggah bukti pembayaran, lalu bukti diverifikasi oleh admin/seller.

1.3 Tujuan Sistem

Sistem dibuat untuk:

Mempermudah seller menjual jasa pembuatan website melalui paket siap harga maupun custom brief.

Mempermudah seller menjual produk fisik dan digital.

Mempermudah pembeli menemukan jasa web dan produk yang sesuai kebutuhan.

Menyediakan tempat untuk menampilkan informasi jasa/produk secara terstruktur.

Menyediakan alur transaksi manual yang jelas melalui instruksi bayar dan bukti pembayaran.

Memungkinkan pembeli dan penjual berkomunikasi melalui WhatsApp.

Menjaga kualitas platform melalui approval seller dan moderasi listing.

Menyediakan sistem pengelolaan bagi administrator.

2. Deskripsi Umum Sistem
2.1 Perspektif Produk

Website merupakan aplikasi berbasis web yang dibangun dengan PHP native dan MySQL, serta dapat diakses menggunakan browser pada komputer maupun perangkat mobile.

Secara umum sistem memiliki empat jenis pengguna:

Guest — pengguna yang belum login.

Buyer (User) — pengguna yang sudah memiliki akun dan berperan sebagai pembeli.

Seller — pengguna yang telah disetujui admin untuk berjualan jasa web dan/atau produk.

Admin — pengguna yang bertugas mengelola, memoderasi, dan memverifikasi sistem.

2.2 Karakteristik Pengguna
Guest

Guest dapat:

Melihat halaman utama.

Melihat katalog listing.

Mencari listing.

Melihat detail listing.

Melihat kategori.

Guest tidak dapat:

Membuat listing.

Membuat order.

Mengunggah bukti pembayaran.

Menambahkan listing ke favorit.

Mengelola profil.

Buyer (User)

Buyer dapat:

Login dan logout.

Mengelola profil.

Melihat katalog dan detail listing.

Membuat order dan mengunggah bukti pembayaran.

Melihat status pesanan.

Menambahkan listing ke favorit.

Memberikan review dan rating setelah order selesai.

Mengirim brief custom.

Menghubungi penjual via WhatsApp.

Melaporkan listing.

Mengajukan diri menjadi seller.

Seller

Seller dapat:

Melakukan semua aksi yang dimiliki buyer.

Membuat, mengubah, dan menghapus listing miliknya sendiri.

Membuat paket jasa berjenjang.

Menjawab brief dan memberi penawaran.

Melihat dan memproses order masuk.

Memverifikasi bukti pembayaran (bersama admin).

Melihat dashboard seller.

Seller tidak dapat:

Mengedit atau menghapus listing seller lain.

Menyetujui pengajuan seller lain.

Memoderasi listing.

Admin

Admin dapat:

Login ke dashboard admin.

Melihat seluruh pengguna dan seluruh listing.

Menyetujui atau menolak pengajuan seller.

Memverifikasi bukti pembayaran order.

Memoderasi listing (menyetujui, menolak, menonaktifkan).

Menghapus listing yang melanggar aturan.

Menonaktifkan akun pengguna.

Mengelola kategori.

Melihat dan mengelola order.

Melihat laporan dari pengguna.

3. Functional Requirements
FR-01 — Registrasi

Sistem harus menyediakan fitur registrasi akun.

Data minimal:

Nama pengguna.

Username.

Email.

Password.

Nomor telepon (opsional).

Ketentuan:

Email harus memiliki format yang valid.

Email tidak boleh digunakan oleh akun lain.

Password harus memenuhi aturan keamanan minimum dan disimpan dalam bentuk hash.

Sistem harus memberikan pesan ketika registrasi berhasil atau gagal.

FR-02 — Login

Sistem harus memungkinkan pengguna masuk menggunakan:

Email.

Password.

Jika data login benar, pengguna diarahkan ke halaman utama atau dashboard sesuai role.

Jika data salah, sistem menampilkan pesan kesalahan.

FR-03 — Logout

Buyer, Seller, dan Admin dapat keluar dari akun.

Setelah logout, session autentikasi harus dihapus atau dibuat tidak berlaku.

FR-04 — Profil Pengguna

User dapat melihat dan mengubah:

Nama.

Username.

Foto profil.

Nomor telepon.

Lokasi.

Deskripsi singkat.

Email akun tidak dapat diubah sembarangan atau harus melalui mekanisme verifikasi jika fitur tersebut tersedia.

FR-05 — Pengajuan Seller

User yang sudah login dapat mengajukan diri menjadi seller.

Data profil toko:

Nama toko.

Deskripsi toko/layanan.

Nomor WhatsApp.

Rekening bank / e-wallet.

Status pengajuan: menunggu persetujuan, disetujui, ditolak.

Ketentuan:

User yang pengajuannya belum disetujui tidak dapat membuat listing.

User yang ditolak dapat memperbaiki data dan mengajukan ulang.

FR-06 — Approval Seller

Admin dapat menyetujui atau menolak pengajuan seller.

Saat disetujui, user memperoleh role seller dan dapat memposting listing.

Saat ditolak, alasan penolakan dapat disimpan dan ditampilkan kepada user.

FR-07 — Membuat Listing

Seller dapat membuat listing dengan tipe product atau service.

Data listing minimal:

Judul.

Deskripsi.

Tipe listing.

Harga.

Kategori.

Lokasi.

Foto (wajib untuk tipe product).

Kondisi (khusus tipe product).

Setelah dibuat, listing dapat berstatus:

Menunggu moderasi.

Aktif.

Ditolak.

Terjual.

Nonaktif.

FR-08 — Mengubah Listing

Pemilik listing dapat mengubah informasi listing miliknya.

Informasi yang dapat diubah meliputi:

Judul.

Deskripsi.

Harga.

Kategori.

Kondisi.

Lokasi.

Foto.

Status.

Paket jasa.

FR-09 — Menghapus Listing

Pemilik listing dapat menghapus atau menonaktifkan listing miliknya.

Listing yang sudah dihapus tidak ditampilkan kepada pengguna umum.

FR-10 — Katalog Listing

Sistem menampilkan daftar listing jasa web dan produk yang tersedia.

Setiap kartu listing minimal menampilkan:

Foto.

Tipe listing (Jasa/Produk).

Judul.

Harga.

Kategori.

Lokasi.

Status.

FR-11 — Detail Listing

Sistem harus menyediakan halaman detail listing.

Informasi yang ditampilkan:

Foto listing.

Tipe listing.

Judul.

Harga.

Deskripsi.

Kategori.

Kondisi (untuk produk).

Lokasi.

Nama toko/penjual.

Rating dan review.

Status listing.

Daftar paket jasa (harga, durasi, jumlah revisi, daftar fitur) untuk listing jasa.

Tombol pilih paket/buat order.

Tombol kirim brief.

Tombol hubungi penjual (WhatsApp).

Tombol favorit.

Tombol laporkan.

FR-12 — Pencarian

User dapat mencari listing berdasarkan kata kunci.

Contoh:

"website toko online"

Sistem menampilkan listing yang memiliki kata kunci yang relevan pada judul atau deskripsi.

FR-13 — Filter Kategori

User dapat melakukan filter berdasarkan:

Kategori (jasa web dan produk).

Tipe listing (service/product).

Harga minimum.

Harga maksimum.

Kondisi (produk).

Lokasi.

FR-14 — Paket Jasa

Seller dapat membuat paket jasa berjenjang pada listing tipe service.

Data paket:

Nama paket (Basic/Pro/Enterprise).

Harga.

Durasi pengerjaan (hari).

Jumlah revisi.

Daftar fitur.

Buyer dapat memilih paket lalu membuat order.

FR-15 — Custom Brief & Penawaran

Buyer dapat mengirim brief kebutuhan website kepada seller.

Data brief:

Deskripsi kebutuhan.

Perkiraan budget.

Deadline.

Seller dapat memberi penawaran berupa harga dan durasi pengerjaan.

Buyer dapat menerima (deal) atau menolak penawaran.

Saat deal, penawaran otomatis menjadi order.

FR-16 — Order

Buyer dapat membuat order dari listing/paket yang dipilih.

Data order:

Kode order unik.

Buyer, seller, listing, dan paket.

Total tagihan.

Order hanya dapat dibuat oleh user yang sudah login.

Listing berstatus terjual tidak dapat menerima order baru.

FR-17 — Instruksi Bayar & Upload Bukti

Sistem menampilkan instruksi bayar manual:

Nomor rekening bank / QRIS statis.

Nominal total tagihan.

Kode order sebagai referensi transfer.

Buyer dapat mengunggah bukti pembayaran.

Ketentuan upload:

Format file JPG, JPEG, PNG, atau WebP.

Ukuran maksimal 2MB.

Nama file disimpan secara acak.

FR-18 — Verifikasi Pembayaran

Admin atau seller dapat memverifikasi bukti pembayaran.

Admin/seller dapat menyetujui (status diverifikasi) atau menolak bukti pembayaran.

Sistem menyimpan bukti pembayaran beserta waktu verifikasi.

FR-19 — Status Order

Setiap order harus melewati status:

menunggu_bukti.

diverifikasi.

proses.

selesai.

batal.

Buyer dan seller dapat melihat status serta riwayat order.

FR-20 — Kontak Penjual

Pembeli dapat menghubungi penjual melalui WhatsApp (deep link wa.me) berdasarkan nomor yang terdaftar pada profil seller.

Informasi kontak ditampilkan sesuai aturan privasi sistem.

FR-21 — Favorit

User yang login dapat menyimpan listing ke daftar favorit.

User dapat:

Menambahkan listing ke favorit.

Menghapus listing dari favorit.

Melihat daftar listing favorit.

FR-22 — Review & Rating

Buyer dapat memberikan review dan rating bintang setelah order selesai.

Ketentuan:

Rating berupa angka 1–5 dan komentar.

Satu order hanya dapat direview satu kali.

Review tampil pada halaman detail listing dan profil seller.

FR-23 — Dashboard Buyer / Seller

Dashboard buyer menampilkan:

Pesanan saya beserta statusnya.

Favorit.

Brief yang pernah dikirim.

Profil.

Dashboard seller menampilkan:

Listing milik saya beserta status dan moderasi.

Paket jasa.

Brief masuk dan penawaran.

Order masuk dan bukti pembayaran.

FR-24 — Dashboard Admin

Admin memiliki dashboard untuk:

Melihat jumlah user, seller, listing, dan order.

Menyetujui/menolak pengajuan seller.

Memverifikasi bukti pembayaran.

Memoderasi listing.

Mengelola user.

Mengelola order.

Mengelola kategori.

Melihat laporan.

FR-25 — Moderasi Listing

Admin dapat:

Menyetujui listing.

Menolak listing.

Menonaktifkan listing.

Melihat alasan laporan.

Jika listing ditolak, sistem menyimpan alasan penolakan.

4. Non-Functional Requirements
NFR-01 — Performance

Halaman utama dan katalog sebaiknya dapat dimuat dalam waktu kurang dari 3 detik pada koneksi internet yang normal.

Pencarian harus memberikan hasil dalam waktu yang wajar.

Gambar listing dan bukti pembayaran harus dikompresi agar tidak terlalu membebani server.

NFR-02 — Security

Password harus disimpan dalam bentuk hash, bukan plaintext.

Setiap form harus dilindungi token CSRF.

Upload foto dan bukti pembayaran harus divalidasi: tipe file (MIME), ukuran maksimal 2MB, dan nama file acak.

Sistem harus menggunakan autentikasi untuk halaman yang membutuhkan login.

Sistem harus menggunakan role gate sesuai hak akses (guest, buyer, seller, admin).

User hanya boleh mengubah atau menghapus listing miliknya sendiri.

Admin memiliki hak akses khusus.

Input pengguna harus divalidasi untuk mencegah serangan seperti SQL Injection dan XSS.

NFR-03 — Usability

Website harus:

Mudah digunakan oleh pengguna baru.

Memiliki navigasi yang jelas.

Menggunakan bahasa yang mudah dipahami.

Menampilkan pesan error yang jelas dan tidak teknis.

NFR-04 — Responsive

Website harus dapat digunakan pada lebar layar:

360px (smartphone).

768px (tablet).

1024px (laptop/desktop).

Desktop dan laptop besar.

NFR-05 — Availability

Sistem diharapkan dapat digunakan selama server aktif dan memiliki koneksi internet.

NFR-06 — Maintainability

Kode program harus:

Terstruktur.

Menggunakan penamaan yang konsisten.

Memisahkan halaman, logika bisnis, dan akses database jika memungkinkan.

Memiliki dokumentasi untuk bagian penting sistem.

NFR-07 — Compatibility

Website dapat digunakan pada browser modern seperti:

Google Chrome.

Mozilla Firefox.

Microsoft Edge.

Safari.

5. Use Case
5.1 Aktor
Aktor	Deskripsi
Guest	Pengunjung yang belum login
Buyer (User)	Pengguna yang sudah login dan berperan sebagai pembeli
Seller	Pengguna yang sudah disetujui admin untuk berjualan
Admin	Pengelola sistem
5.2 Use Case Utama
ID	Use Case	Aktor
UC-01	Registrasi	Guest
UC-02	Login	Buyer/Seller/Admin
UC-03	Logout	Buyer/Seller/Admin
UC-04	Melihat katalog	Guest/User
UC-05	Mencari listing	Guest/User
UC-06	Filter kategori	Guest/User
UC-07	Melihat detail listing	Guest/User
UC-08	Mengelola profil	User
UC-09	Mengajukan jadi seller	User
UC-10	Menyetujui/menolak seller	Admin
UC-11	Membuat listing	Seller
UC-12	Mengedit listing	Seller
UC-13	Menghapus listing	Seller
UC-14	Membuat paket jasa	Seller
UC-15	Mengirim brief custom	User
UC-16	Memberi penawaran brief	Seller
UC-17	Membuat order	User
UC-18	Upload bukti pembayaran	User
UC-19	Verifikasi pembayaran	Seller/Admin
UC-20	Memberi review & rating	User
UC-21	Menambahkan favorit	User
UC-22	Menghubungi penjual (WhatsApp)	User
UC-23	Melaporkan listing	User
UC-24	Memoderasi listing	Admin
UC-25	Mengelola pengguna	Admin
UC-26	Mengelola order	Admin
UC-27	Mengelola kategori	Admin
6. Database Requirements

Database minimal terdiri dari tabel berikut:

Users
Field	Tipe	Keterangan
id	Integer	Primary key
name	String	Nama user
username	String	Username unik
email	String	Email unik
password	String	Password yang telah di-hash
phone	String	Nomor telepon
location	String	Lokasi
avatar	String	Foto profil
role	Enum	buyer/seller/admin
status	Enum	active/inactive
created_at	DateTime	Waktu dibuat
updated_at	DateTime	Waktu diperbarui
Seller Profiles
Field	Tipe	Keterangan
id	Integer	Primary key
user_id	Integer	ID pemilik (users)
store_name	String	Nama toko
deskripsi	Text	Deskripsi toko/layanan
approval	Enum	pending/approved/rejected
rekening	String	Rekening bank / e-wallet
created_at	DateTime	Waktu dibuat
Listings
Field	Tipe	Keterangan
id	Integer	Primary key
seller_id	Integer	Pemilik listing (users)
type	Enum	product/service
title	String	Judul listing
description	Text	Deskripsi listing
category_id	Integer	Kategori
price	Decimal	Harga
condition	Enum	Kondisi barang (produk)
location	String	Lokasi
status	Enum	aktif/terjual/nonaktif
moderation	Enum	pending/approved/rejected
created_at	DateTime	Waktu dibuat
updated_at	DateTime	Waktu diperbarui
Listing Packages
Field	Tipe	Keterangan
id	Integer	Primary key
listing_id	Integer	ID listing jasa
name	String	Nama paket (Basic/Pro/Enterprise)
price	Decimal	Harga paket
duration_days	Integer	Durasi pengerjaan (hari)
revisions	Integer	Jumlah revisi
features	Text	Daftar fitur paket
Listing Images
Field	Tipe	Keterangan
id	Integer	Primary key
listing_id	Integer	ID listing
image_url	String	Lokasi gambar
is_primary	Boolean	Gambar utama
Categories
Field	Tipe	Keterangan
id	Integer	Primary key
name	String	Nama kategori
type	Enum	jasa/produk
created_at	DateTime	Waktu dibuat
Briefs
Field	Tipe	Keterangan
id	Integer	Primary key
buyer_id	Integer	ID pembeli
seller_id	Integer	ID seller yang dituju
listing_id	Integer	ID listing jasa
kebutuhan	Text	Deskripsi kebutuhan website
budget	Decimal	Perkiraan budget
deadline	Date	Deadline yang diharapkan
penawaran	Decimal	Harga penawaran seller
status	Enum	dikirim/penawaran/deal/ditolak
created_at	DateTime	Waktu dikirim
Orders
Field	Tipe	Keterangan
id	Integer	Primary key
order_code	String	Kode order unik
buyer_id	Integer	ID pembeli
seller_id	Integer	ID penjual
listing_id	Integer	ID listing
package_id	Integer	ID paket (opsional)
total	Decimal	Total tagihan
payment_proof	String	Bukti pembayaran yang diunggah
status	Enum	menunggu_bukti/diverifikasi/proses/selesai/batal
created_at	DateTime	Waktu order dibuat
Reviews
Field	Tipe	Keterangan
id	Integer	Primary key
order_id	Integer	ID order yang direview
buyer_id	Integer	ID pembeli pemberi review
seller_id	Integer	ID seller penerima review
rating	Integer	Rating bintang 1–5
comment	Text	Komentar review
created_at	DateTime	Waktu review dibuat
Favorites
Field	Tipe	Keterangan
id	Integer	Primary key
user_id	Integer	ID user
listing_id	Integer	ID listing
created_at	DateTime	Waktu dibuat
Reports
Field	Tipe	Keterangan
id	Integer	Primary key
user_id	Integer	Pelapor
listing_id	Integer	Listing yang dilaporkan
reason	String	Alasan laporan
status	Enum	pending/resolved/rejected
created_at	DateTime	Waktu dibuat
Relationship

1:N users → seller_profiles

1:N users → listings (sebagai seller)

1:N users → orders (sebagai buyer)

1:N listings → listing_packages

1:N listings → listing_images

1:N listings → orders

1:N orders → reviews

1:N categories → listings

N:N users ↔ listings melalui favorites

7. Business Rules

Satu email hanya dapat digunakan oleh satu akun.

Password harus disimpan dalam bentuk hash.

User yang tidak login tidak dapat membuat order, listing, atau favorit.

User harus menyetujui pengajuan seller yang disetujui admin sebelum dapat membuat listing.

User hanya dapat mengedit dan menghapus listing miliknya sendiri.

Listing harus melewati proses moderasi sebelum tampil kepada publik.

Setiap listing harus memiliki judul, harga, kategori, dan deskripsi.

Harga listing dan paket harus berupa angka positif.

Listing bertipe product wajib memiliki minimal satu foto.

Listing bertipe service wajib memiliki minimal satu paket jasa.

Order yang dibuat dari listing berstatus terjual tidak boleh diterima.

Bukti pembayaran wajib diunggah sebelum order dapat diverifikasi oleh admin/seller.

Review hanya dapat diberikan oleh buyer untuk order berstatus selesai.

Admin dapat menonaktifkan listing yang melanggar aturan.

Admin dapat menonaktifkan akun pengguna.

Admin dapat menyetujui atau menolak pengajuan seller.

Seller tidak boleh menjual jasa atau produk yang dilarang oleh aturan website.

User yang tidak login tidak dapat melaporkan listing.

8. System Architecture

Sistem menggunakan arsitektur client-server dengan PHP native dan MySQL.

+----------------------+
|      User Browser    |
|  Desktop / Mobile    |
+----------+-----------+
           |
           | HTTP / HTTPS
           v
+----------------------+
|       Frontend       |
| HTML / CSS / JS      |
+----------+-----------+
           |
           | PHP (form & query)
           v
+----------------------+
|       Backend        |
| PHP Native           |
| Auth / Business Logic|
+----------+-----------+
           |
           v
+----------------------+
|      Database        |
|        MySQL         |
| Users / Listings /   |
| Orders / Reviews /   |
| Categories / Reports |
+----------------------+


Teknologi yang digunakan:

Frontend

HTML.

CSS.

JavaScript.

Backend

PHP Native (tanpa framework).

Database

MySQL.

9. User Interface Requirements
Halaman Utama

Halaman utama minimal memiliki:

Logo website.

Search bar.

Tombol login/register.

Kategori jasa web dan produk.

Daftar listing terbaru.

Tombol jual (ajukan jadi seller / buat listing).

Halaman Detail Listing

Menampilkan:

Foto.

Tipe listing (Jasa/Produk).

Judul.

Harga.

Kategori.

Kondisi.

Lokasi.

Deskripsi.

Informasi penjual/toko.

Rating dan review.

Daftar paket jasa (untuk listing jasa).

Tombol pilih paket / kirim brief.

Tombol hubungi penjual (WhatsApp).

Tombol favorit.

Tombol laporan.

Halaman Checkout / Instruksi Bayar

Menampilkan:

Kode order.

Rincian tagihan.

Nominal transfer.

Nomor rekening / QRIS statis.

Form upload bukti pembayaran.

Status order.

Dashboard Buyer

Menampilkan:

Profil.

Pesanan saya dan statusnya.

Favorit.

Brief yang pernah dikirim.

Dashboard Seller

Menampilkan:

Profil toko dan status approval.

Daftar listing beserta status moderasi.

Paket jasa.

Brief masuk dan penawaran.

Order masuk dan bukti pembayaran.

Tombol tambah listing.

Dashboard Admin

Menampilkan:

Statistik website.

Daftar pengajuan seller (approval).

Daftar bukti pembayaran untuk diverifikasi.

Daftar listing (moderasi).

Daftar user.

Daftar order.

Daftar laporan.

Manajemen kategori.

10. Acceptance Criteria

Sistem dianggap memenuhi kebutuhan apabila:

User dapat membuat akun.

User dapat login dan logout.

User dapat mengelola profil.

User dapat mengajukan diri menjadi seller dan menunggu approval admin.

Admin dapat menyetujui atau menolak pengajuan seller.

Seller dapat membuat listing jasa atau produk.

Seller dapat mengunggah foto listing.

Seller dapat mengedit dan menghapus listing miliknya.

Seller dapat membuat paket jasa berjenjang.

Pengunjung dapat mencari listing.

Pengunjung dapat menggunakan filter kategori.

Pengunjung dapat melihat detail listing.

User dapat mengirim brief custom dan seller dapat memberi penawaran.

User dapat membuat order dan melihat instruksi bayar.

User dapat mengunggah bukti pembayaran.

Admin/seller dapat memverifikasi pembayaran dan mengubah status order.

User dapat menyimpan listing ke favorit.

User dapat menghubungi penjual via WhatsApp.

User dapat melaporkan listing.

Buyer dapat memberikan review dan rating setelah order selesai.

Admin dapat mengelola user.

Admin dapat mengelola order dan kategori.

Admin dapat memoderasi listing.

Website dapat digunakan pada lebar 360px, 768px, dan 1024px.

11. Future Development

Fitur berikut dapat dikembangkan pada versi berikutnya:

Payment gateway otomatis.

Checkout dan pembayaran online otomatis.

Payout otomatis ke rekening seller.

Chat real-time.

Sistem notifikasi.

Integrasi jasa pengiriman.

Tracking pengiriman otomatis.

Rekomendasi berbasis AI.

Sistem lelang/nego harga.

Verifikasi identitas pengguna.

Aplikasi Android/iOS.

12. Kesimpulan

SESSIONS — Marketplace Jasa Web & Produk dirancang untuk menyediakan platform multi-seller yang sederhana dan mudah digunakan, tempat pengguna menjual jasa pembuatan website dan produk fisik/digital. Sistem memungkinkan pengguna membuat listing, mencari dan melihat detail listing, membuat order dengan instruksi bayar manual dan upload bukti pembayaran, menghubungi penjual, serta memberikan review dan rating.

Untuk tahap awal proyek sekolah/kuliah, fokus utama adalah autentikasi pengguna, approval seller, CRUD listing, paket jasa, custom brief, order manual beserta verifikasi pembayaran, pencarian, kategori, favorit, review, dan dashboard admin. Fitur seperti payment gateway otomatis, pengiriman, chat real-time, dan rekomendasi AI dapat dikembangkan pada tahap selanjutnya.

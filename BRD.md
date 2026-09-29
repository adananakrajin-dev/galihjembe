Business Requirements Document (BRD)
SESSIONS — Marketplace Jasa Web & Produk

Dokumen ini berisi kebutuhan bisnis dan fungsional untuk pengembangan SESSIONS, marketplace multi-seller tempat pengguna menjual jasa web (jasa pembuatan website) dan produk (fisik/digital) secara bersamaan dalam satu platform.

1. Informasi Proyek
Informasi	Detail
Nama Proyek	SESSIONS — Marketplace Jasa Web & Produk
Jenis Proyek	Marketplace Multi-Seller (Jasa Web & Produk)
Platform	Website (PHP Native + MySQL)
Target Pengguna	Pelajar, mahasiswa, pekerja lepas, dan masyarakat umum
Metode Transaksi	Manual — transfer bank / QRIS statis + upload bukti, diverifikasi admin/seller
Status	Development
Versi Dokumen	1.1

2. Latar Belakang

Saat ini jasa pembuatan website banyak ditawarkan melalui media sosial atau forum secara tidak terstruktur, sehingga calon pembeli kesulitan membandingkan layanan, harga, durasi pengerjaan, dan jumlah revisi. Di sisi lain, banyak developer perorangan (freelancer, pelajar, mahasiswa) yang memiliki kemampuan membuat website tetapi tidak memiliki kanal penjualan yang mudah.

Selain jasa web, pengguna juga membutuhkan tempat menjual produk fisik maupun digital. Kedua kebutuhan tersebut hadir di satu platform yang sama sehingga pengguna cukup membuat satu akun.

Oleh karena itu, diperlukan sebuah platform yang mempertemukan penjual jasa web dan penjual produk dengan pembeli secara mudah, transparan, dan praktis.

Website ini dibuat sebagai marketplace multi-seller tempat pengguna dapat menjual jasa pembuatan website (melalui paket siap harga maupun custom brief) dan produk (fisik/digital) serta membeli layanan/produk dari seller lain.

3. Tujuan Proyek

Tujuan dari pembuatan website ini adalah:

Menyediakan platform bagi seller untuk menjual jasa pembuatan website melalui paket siap harga maupun penawaran custom.

Menyediakan tempat bagi pengguna menjual produk fisik dan digital bersama jasa web dalam satu platform.

Memudahkan pembeli mencari dan membandingkan jasa web serta produk sesuai kebutuhan dan budget.

Menyediakan proses transaksi manual yang sederhana dan transparan melalui instruksi pembayaran dan upload bukti.

Menjaga kualitas platform melalui persetujuan seller oleh admin dan moderasi listing.

Menyediakan informasi jasa/produk yang jelas kepada calon pembeli.

Membuat proses jual beli jasa web dan produk menjadi lebih praktis dan terorganisir.

4. Permasalahan

Beberapa permasalahan yang ingin diselesaikan melalui website ini:

Calon pembeli sulit menemukan penyedia jasa pembuatan website yang terpercaya dan transparan harganya.

Penjual jasa/freelancer tidak memiliki kanal penjualan yang tersentralisasi dan mudah ditemukan.

Pesanan jasa web sering tidak memiliki format brief dan kesepakatan yang jelas antara pembeli dan penjual.

Transaksi jarak jauh rawan kesalahpahaman karena tidak ada alur pembayaran dan bukti yang terstruktur.

Listing berkualitas rendah, palsu, atau melanggar aturan dapat menurunkan kepercayaan pengguna terhadap marketplace.

Belum adanya platform sederhana yang memuat jasa web dan produk dalam satu tempat untuk kebutuhan proyek ini.

5. Solusi

Solusi yang ditawarkan adalah membuat website marketplace sederhana yang memungkinkan pengguna untuk:

Membuat akun.

Mengajukan diri menjadi seller dengan mengisi profil toko (menunggu approval admin).

Menjual jasa web melalui paket siap harga berjenjang (Basic/Pro/Enterprise).

Menjual jasa web melalui custom brief dan penawaran.

Menjual produk fisik atau digital.

Mengunggah foto listing.

Menentukan harga listing.

Mencari listing berdasarkan kata kunci atau kategori.

Melihat detail listing.

Membuat order dan menerima instruksi pembayaran (transfer bank/QRIS statis).

Mengunggah bukti pembayaran.

Menghubungi penjual melalui WhatsApp.

Menyimpan listing favorit.

Memberikan review dan rating bintang setelah order selesai.

6. Stakeholder
Stakeholder	Peran
Pembeli (Buyer)	Mencari dan membeli jasa web / produk
Penjual (Seller)	Menjual jasa web dan/atau produk
Admin	Mengelola pengguna, approve seller, verifikasi pembayaran, dan moderasi listing
Developer	Mengembangkan dan memelihara website

7. Target Pengguna
7.1 Pembeli (Buyer)

Pengguna yang ingin membeli jasa pembuatan website atau produk sesuai kebutuhan dan budget, dengan informasi harga, durasi, dan ketentuan yang jelas.

7.2 Penjual (Seller)

Pengguna yang menawarkan jasa pembuatan website (melalui paket atau custom brief) dan/atau produk fisik/digital. Seller wajib mendapat persetujuan admin sebelum dapat memposting listing.

7.3 Admin

Pengguna dengan hak akses khusus untuk mengelola pengguna, menyetujui pengajuan seller, memverifikasi pembayaran, memoderasi listing, dan mengelola data dalam website.

8. Ruang Lingkup
8.1 Fitur yang Termasuk (MVP)

Autentikasi: registrasi, login, logout, dan profil pengguna.

Katalog listing (jasa web dan produk).

Pencarian dan filter kategori.

Detail listing.

CRUD listing khusus seller (tambah, edit, hapus).

Paket jasa berjenjang (Basic/Pro/Enterprise).

Custom brief: buyer kirim brief kebutuhan → seller beri penawaran → deal menjadi order.

Order manual + upload bukti pembayaran.

Status order.

Kontak penjual via WhatsApp.

Favorit.

Dashboard buyer, seller, dan admin.

Approval seller oleh admin.

Moderasi listing.

Review dan rating bintang setelah order selesai.

8.2 Fitur yang Tidak Termasuk (Out of Scope)

Untuk versi awal proyek, fitur berikut tidak termasuk dalam scope:

Payment gateway otomatis.

Ekspedisi dan tracking otomatis.

Chat real-time.

Sistem rekomendasi berbasis AI.

Aplikasi mobile.

Sistem lelang (auction).

Integrasi dengan marketplace lain.

8.3 Cara Transaksi pada Versi Awal (Manual)

Transaksi pada MVP dilakukan secara manual tanpa payment gateway otomatis:

Buyer membuat order.

Sistem menampilkan instruksi bayar (transfer bank / QRIS statis).

Buyer upload bukti pembayaran.

Admin/seller memverifikasi bukti pembayaran.

Status order berjalan: menunggu_bukti → diverifikasi → proses → selesai (atau batal).

Payment gateway tetap menjadi future development.

8.4 Dua Cara Menjual Jasa Web

Paket siap harga: seller membuat paket berjenjang (Basic/Pro/Enterprise) yang memuat harga, durasi pengerjaan, jumlah revisi, dan daftar fitur. Buyer memilih paket lalu membuat order.

Custom brief: buyer mengirim brief kebutuhan website → seller memberi penawaran (harga dan durasi) → terjadi kesepakatan (deal) → penawaran menjadi order.

9. Kebutuhan Fungsional
ID	Fitur	Deskripsi	Prioritas
FR-01	Registrasi	Pengguna dapat membuat akun baru	High
FR-02	Login	Pengguna dapat masuk ke dalam sistem	High
FR-03	Logout	Pengguna dapat keluar dari akun	Medium
FR-04	Profil	Pengguna dapat melihat dan mengubah profil	Medium
FR-05	Pengajuan Seller	Pengguna dapat mengajukan diri menjadi seller dengan mengisi profil toko	High
FR-06	Approval Seller	Admin menyetujui atau menolak pengajuan seller sebelum seller dapat memposting listing	High
FR-07	Tambah Listing	Seller dapat menambahkan listing jasa web atau produk	High
FR-08	Edit Listing	Seller dapat mengubah listing miliknya sendiri	High
FR-09	Hapus Listing	Seller dapat menghapus listing miliknya sendiri	High
FR-10	Katalog Listing	Pengguna dapat melihat daftar listing jasa dan produk	High
FR-11	Pencarian	Pengguna dapat mencari listing berdasarkan kata kunci	High
FR-12	Kategori & Filter	Pengguna dapat memfilter listing berdasarkan kategori jasa web/produk	Medium
FR-13	Detail Listing	Pengguna dapat melihat informasi lengkap listing	High
FR-14	Paket Jasa	Seller dapat membuat paket jasa berjenjang (Basic/Pro/Enterprise) berisi harga, durasi, jumlah revisi, dan daftar fitur	High
FR-15	Custom Brief	Buyer mengirim brief kebutuhan jasa web, seller memberi penawaran, dan kesepakatan menjadi order	High
FR-16	Buat Order	Buyer dapat membuat order dari listing atau kesepakatan penawaran	High
FR-17	Instruksi Bayar & Upload Bukti	Sistem menampilkan instruksi bayar (transfer bank/QRIS statis) dan buyer dapat mengunggah bukti pembayaran	High
FR-18	Verifikasi Pembayaran	Admin/seller memverifikasi bukti pembayaran dan mengubah status order	High
FR-19	Status Order	Setiap order memiliki status: menunggu_bukti, diverifikasi, proses, selesai, batal	High
FR-20	Kontak Penjual	Buyer dapat menghubungi penjual melalui WhatsApp	High
FR-21	Favorit	Pengguna yang login dapat menyimpan listing ke daftar favorit	Medium
FR-22	Review & Rating	Buyer dapat memberikan review dan rating bintang setelah order selesai	Medium
FR-23	Dashboard Buyer/Seller	Buyer melihat pesanan dan favorit; seller melihat listing, paket, brief, dan order masuk	High
FR-24	Dashboard Admin	Admin dapat approve seller, verifikasi pembayaran, memoderasi listing, serta mengelola user, order, dan kategori	High
FR-25	Laporan Listing	Pengguna dapat melaporkan listing yang dianggap melanggar aturan	Medium

10. Kebutuhan Non-Fungsional
10.1 Usability

Website harus memiliki tampilan yang sederhana, jelas, dan mudah digunakan oleh pengguna baru.

10.2 Performance

Halaman utama dan halaman listing harus dapat dimuat dalam waktu kurang dari 3 detik pada koneksi internet normal sehingga pengguna tidak mengalami gangguan saat menggunakan sistem.

10.3 Security

Password pengguna harus disimpan dalam bentuk hash, bukan plain text.

Setiap form harus dilindungi token CSRF.

Upload foto/bukti pembayaran harus divalidasi: tipe file (MIME), ukuran maksimal 2MB, dan disimpan dengan nama acak.

Pengguna hanya dapat mengakses fitur sesuai dengan hak aksesnya (role gate).

Data pengguna harus dilindungi dari akses yang tidak sah.

10.4 Responsive

Website harus dapat digunakan pada berbagai ukuran layar, termasuk:

360px (smartphone).

768px (tablet).

1024px (laptop/desktop).

Desktop dan laptop besar.

10.5 Availability

Website diharapkan dapat diakses selama server atau hosting dalam kondisi aktif.

11. Alur Sistem
11.1 Alur Buyer
Lihat Katalog
      ↓
Melihat Detail Listing
      ↓
Login / Registrasi
      ↓
Buat Order (pilih paket / deal penawaran)
      ↓
Menerima Instruksi Bayar
      ↓
Upload Bukti Pembayaran
      ↓
Order Diverifikasi & Diproses Seller
      ↓
Order Selesai
      ↓
Beri Review & Rating

11.2 Alur Seller
Daftar Akun
  ↓
Ajukan Jadi Seller (isi profil toko)
  ↓
Menunggu Approval Admin
  ↓
Posting Listing / Paket Jasa
  ↓
Menerima Order atau Brief Custom
  ↓
Memberi Penawaran (jika custom brief)
  ↓
Mengerjakan Pesanan
  ↓
Order Selesai

11.3 Alur Admin
Login Admin
      ↓
Dashboard Admin
      ↓
Approve Pengajuan Seller
      ↓
Verifikasi Bukti Pembayaran
      ↓
Moderasi Listing
      ↓
Kelola User / Order / Kategori
      ↓
Data Diperbarui

12. Data yang Dibutuhkan
12.1 Data Pengguna (users)
Field	Deskripsi
user_id	ID unik pengguna
name	Nama pengguna
email	Email pengguna
username	Username pengguna
password	Password pengguna (hash)
phone	Nomor telepon
location	Lokasi pengguna
avatar	Foto profil
role	Role pengguna (guest/buyer/seller/admin)
status	Status akun (active/inactive)

12.2 Data Profil Seller (seller_profiles)
Field	Deskripsi
seller_id	ID unik profil seller
user_id	ID pemilik (users)
store_name	Nama toko
deskripsi	Deskripsi toko/layanan
approval	Status persetujuan admin (pending/approved/rejected)
rekening_info	Rekening bank / e-wallet untuk penerimaan pembayaran
created_at	Waktu profil dibuat

12.3 Data Listing (listings)
Field	Deskripsi
listing_id	ID unik listing
seller_id	ID pemilik listing (users)
type	Tipe listing: product atau service
title	Judul listing
description	Deskripsi listing
category_id	ID kategori
price	Harga
condition	Kondisi (khusus tipe product)
location	Lokasi
status	Status listing (tersedia/terjual/nonaktif)
moderation	Status moderasi (menunggu/disetujui/ditolak)
created_at	Waktu listing dibuat

12.4 Data Paket Jasa (listing_packages)
Field	Deskripsi
package_id	ID unik paket
listing_id	ID listing jasa terkait
name	Nama paket (Basic/Pro/Enterprise)
price	Harga paket
duration_days	Durasi pengerjaan (hari)
revisions	Jumlah revisi yang diberikan
features	Daftar fitur paket

12.5 Data Gambar Listing & Kategori
Field	Deskripsi
listing_images: image_id, listing_id, image_url, is_primary	Gambar listing (wajib minimal 1 untuk tipe product)
categories: category_id, name, type	Kategori jasa web dan produk

12.6 Data Brief (briefs)
Field	Deskripsi
brief_id	ID unik brief
buyer_id	ID pembeli pengirim brief
seller_id	ID seller yang dituju
listing_id	ID listing jasa terkait
kebutuhan	Deskripsi kebutuhan website
budget	Perkiraan budget
deadline	Deadline yang diharapkan
penawaran	Harga dan durasi penawaran seller
status	Status brief (dikirim/diberi penawaran/deal/ditolak)
created_at	Waktu brief dikirim

12.7 Data Order (orders)
Field	Deskripsi
order_code	Kode order unik
buyer_id	ID pembeli
seller_id	ID penjual
listing_id	ID listing yang dipesan
package_id	ID paket yang dipilih (opsional)
total	Total tagihan
payment_proof	Bukti pembayaran yang diunggah
status	Status order (menunggu_bukti/diverifikasi/proses/selesai/batal)
created_at	Waktu order dibuat

12.8 Data Review (reviews)
Field	Deskripsi
review_id	ID unik review
order_id	ID order yang direview
buyer_id	ID pembeli pemberi review
seller_id	ID seller penerima review
rating	Rating bintang (1–5)
comment	Komentar review
created_at	Waktu review dibuat

12.9 Data Favorit & Laporan
Field	Deskripsi
favorites: user_id, listing_id, created_at	Listing yang disimpan pengguna
reports: report_id, user_id, listing_id, reason, status, created_at	Laporan listing dari pengguna

12.10 Relasi Data

1:N users → listings (satu user dapat memiliki banyak listing).

1:N users → orders sebagai buyer (satu user dapat membuat banyak order).

1:N listings → listing_packages (satu listing jasa dapat memiliki banyak paket).

1:N listings → listing_images (satu listing dapat memiliki banyak gambar).

1:N listings → orders (satu listing dapat menerima banyak order).

1:N orders → reviews (satu order hanya dapat direview satu kali).

N:N users ↔ listings melalui favorites (favorit).

1:N categories → listings (satu kategori berisi banyak listing).

13. Kategori
13.1 Kategori Jasa Web

Landing Page

Company Profile

Toko Online

Custom App/Bot

13.2 Kategori Produk

Elektronik

Fashion

Buku

Furnitur

Peralatan Rumah Tangga

Hobi

Kendaraan

Aksesoris

Lainnya

14. Kondisi Barang

Untuk listing bertipe produk, penjual wajib memberikan informasi kondisi barang. Listing bertipe jasa tidak memerlukan kondisi. Contoh kategori kondisi:

Kondisi	Deskripsi
Baru	Barang belum pernah digunakan
Seperti Baru	Pernah digunakan tetapi masih dalam kondisi sangat baik
Bekas - Baik	Barang memiliki sedikit tanda penggunaan
Bekas - Cukup	Barang masih dapat digunakan tetapi memiliki beberapa kekurangan

15. Business Rules

Satu email hanya dapat digunakan oleh satu akun dan password disimpan dalam bentuk hash.

Pengguna harus memiliki akun untuk membeli, menjual, menyimpan favorit, dan memberikan review.

Pengajuan menjadi seller harus disetujui admin sebelum seller dapat memposting listing.

Seller hanya boleh mengedit dan menghapus listing miliknya sendiri.

Listing harus melewati proses moderasi admin sebelum tampil kepada publik.

Setiap listing harus memiliki judul, harga, kategori, dan deskripsi.

Harga listing harus berupa angka positif.

Listing bertipe produk wajib memiliki minimal satu foto.

Listing bertipe service wajib memiliki minimal satu paket jasa atau penawaran custom.

Order untuk listing yang berstatus terjual tidak dapat menerima order baru.

Order hanya dapat dibuat oleh buyer yang sudah login dan menampilkan instruksi bayar yang jelas.

Bukti pembayaran wajib diunggah sebelum order dapat diverifikasi.

Review dan rating hanya dapat diberikan buyer setelah order berstatus selesai.

Admin/seller dapat menolak bukti pembayaran yang tidak sesuai dan membatalkan order.

Admin dapat memoderasi listing, menyetujui/menolak pengajuan seller, dan mengelola data pengguna.

Pengguna tidak diperbolehkan menjual jasa atau produk yang dilarang oleh peraturan website.

16. Hak Akses Pengguna
Fitur	Guest	Buyer	Seller	Admin
Melihat katalog	✅	✅	✅	✅
Pencarian & filter	✅	✅	✅	✅
Melihat detail listing	✅	✅	✅	✅
Membuat order & upload bukti	❌	✅	✅	✅
Favorit & review	❌	✅	✅	✅
Ajukan jadi seller	❌	✅	❌	❌
Membuat listing	❌	❌	✅	❌
Edit/hapus listing sendiri	❌	❌	✅	❌
Membuat paket jasa & menjawab brief	❌	❌	✅	❌
Memverifikasi pembayaran	❌	❌	✅	✅
Approval seller	❌	❌	❌	✅
Moderasi listing	❌	❌	❌	✅
Mengelola user/order/kategori	❌	❌	❌	✅

17. Halaman Website

Website direncanakan memiliki beberapa halaman utama:

Public

/

/listings

/listings/:id

/login

/register

/contact

User (Buyer)

/profile

/orders

/orders/:code

/checkout/:code (instruksi bayar & upload bukti)

/briefs (buat brief & brief saya)

/favorites

Seller

/my-listings

/listings/create

/listings/:id/edit

/briefs (memberi penawaran)

/seller (dashboard seller)

Admin

/admin

/admin/sellers (approval seller)

/admin/listings (moderasi listing)

/admin/users

/admin/orders

/admin/categories

18. Indikator Keberhasilan

Proyek dianggap berhasil apabila:

Pengguna dapat melakukan registrasi, login, dan logout.

Pengguna dapat mengajukan diri menjadi seller dan menunggu approval admin.

Katalog menampilkan listing jasa web dan produk.

Pengguna dapat mencari dan memfilter listing berdasarkan kategori.

Pengguna dapat melihat detail listing.

Seller dapat menambah, mengedit, dan menghapus listing miliknya.

Seller dapat membuat paket jasa berjenjang.

Buyer dapat mengirim brief custom dan menerima penawaran seller.

Buyer dapat membuat order, melihat instruksi bayar, dan mengunggah bukti pembayaran.

Admin/seller dapat memverifikasi pembayaran dan status order berjalan sesuai alur.

Buyer dapat memberikan review dan rating bintang setelah order selesai.

Pembeli dapat menghubungi penjual via WhatsApp.

Admin dapat menyetujui pengajuan seller dan memoderasi listing.

Admin dapat mengelola pengguna, order, dan kategori.

Website dapat digunakan dengan baik melalui desktop dan smartphone serta tetap responsif pada lebar 360px, 768px, dan 1024px.

19. Risiko Proyek
Risiko	Dampak	Mitigasi
Listing tidak sesuai/menyesatkan	Pembeli mendapatkan informasi yang salah	Moderasi listing dan fitur laporan
Penyalahgunaan akun seller	Data pengguna dan platform disalahgunakan	Persetujuan seller oleh admin serta autentikasi dan validasi
Jasa/produk ilegal atau dilarang	Masalah keamanan dan pelanggaran aturan	Moderasi oleh admin
Bukti pembayaran palsu	Pesanan diproses tanpa pembayaran	Verifikasi bukti oleh admin/seller dan pencocokan nominal
Transaksi manual tidak jelas	Kekecewaan buyer karena status order membingungkan	Status order dan instruksi bayar yang jelas
Server mengalami gangguan	Website tidak dapat diakses	Menggunakan hosting yang sesuai
Foto/bukti terlalu besar	Website menjadi lambat	Membatasi ukuran unggahan (maks 2MB) dan mengoptimalkan gambar

20. Prioritas Pengembangan
Phase 1 — Core

Registrasi

Login

Logout

Profil pengguna

Katalog listing

Detail listing

Pencarian dan filter kategori

CRUD listing (seller)

Approval seller oleh admin

Phase 2 — Fitur Jasa Web

Paket jasa berjenjang

Custom brief dan penawaran

Kontak penjual via WhatsApp

Favorit

Phase 3 — Transaksi Manual

Buat order

Instruksi bayar

Upload bukti pembayaran

Verifikasi pembayaran

Status order

Review dan rating

Phase 4 — Dashboard & Admin

Dashboard buyer

Dashboard seller

Dashboard admin

Verifikasi pembayaran oleh admin

Moderasi listing

Manajemen pengguna, order, dan kategori

21. Future Development

Fitur yang dapat dikembangkan pada versi berikutnya:

Payment gateway otomatis.

Checkout dan pembayaran online otomatis.

Payout otomatis ke rekening seller.

Sistem chat real-time.

Sistem notifikasi.

Integrasi jasa ekspedisi.

Tracking pengiriman otomatis.

Sistem rekomendasi berbasis AI.

Aplikasi mobile.

Sistem lelang (auction).

Verifikasi identitas pengguna.

22. Kesimpulan

SESSIONS — Marketplace Jasa Web & Produk dibuat untuk menyediakan platform yang sederhana dan mudah digunakan bagi pengguna yang ingin menjual maupun membeli jasa pembuatan website dan produk.

Sistem akan menyediakan fitur utama seperti registrasi, login, approval seller, katalog listing, pencarian dan kategori, detail listing, paket jasa, custom brief, order manual dengan upload bukti pembayaran, kontak penjual via WhatsApp, favorit, review dan rating, serta dashboard bagi buyer, seller, dan admin.

Dengan adanya website ini, diharapkan proses jual beli jasa web dan produk menjadi lebih mudah, praktis, transparan, dan terorganisir.

23. Status Dokumen
Versi	Tanggal	Status	Keterangan
1.0	2026-09-29	Draft	Dokumen awal kebutuhan proyek
1.1	2026-09-29	Revisi — disesuaikan dengan visi marketplace jasa web & produk multi-seller	Revisi menyeluruh: judul, ruang lingkup, FR, data, alur sistem, hak akses, dan sitemap

-- ============================================================
-- SESSIONS — Marketplace Jasa Web & Produk
-- Skema database (MySQL / MariaDB)
--
-- Cara pakai:
--   1. Via migrasi (disarankan, idempotent):
--        php database/migrate.php
--   2. Atau import manual lewat phpMyAdmin:
--        impor file ini ke database `ukk_login`
-- ============================================================

SET NAMES utf8mb4;

-- ── Pengguna ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL DEFAULT '',
    email       VARCHAR(150)  NULL,
    username    VARCHAR(20)   NOT NULL,
    password    VARCHAR(255)  NOT NULL,          -- hash password_hash(), bukan plaintext
    phone       VARCHAR(25)   NULL,
    location    VARCHAR(100)  NULL,
    avatar      VARCHAR(255)  NULL,
    role        ENUM('buyer','seller','admin')    NOT NULL DEFAULT 'buyer',
    status      ENUM('active','inactive')         NOT NULL DEFAULT 'active',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Profil toko (multi-seller, menunggu approval admin) ──────
CREATE TABLE IF NOT EXISTS seller_profiles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    store_name  VARCHAR(100) NOT NULL,
    store_desc  TEXT NULL,
    payout_info VARCHAR(100) NULL,               -- rekening / e-wallet pencairan
    home_address TEXT NULL,                       -- alamat rumah (HANYA tampil utk admin, verifikasi anti-penipuan)
    approval    ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_seller_user (user_id),
    CONSTRAINT fk_seller_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Kategori ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(60) NOT NULL,
    slug      VARCHAR(60) NOT NULL,
    type      ENUM('product','service','all') NOT NULL DEFAULT 'all',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Listing (jasa ATAU produk — kolom `type`) ────────────────
CREATE TABLE IF NOT EXISTS listings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    seller_id   INT NOT NULL,
    category_id INT NULL,
    type        ENUM('product','service') NOT NULL DEFAULT 'product',
    title       VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price       DECIMAL(15,2) NOT NULL DEFAULT 0, -- harga dasar / mulai
    `condition` ENUM('baru','seperti-baru','bekas-baik','bekas-cukup') NULL, -- produk saja
    location    VARCHAR(100) NULL,
    province    VARCHAR(100) NULL,                -- cascade wilayah: nama provinsi
    regency     VARCHAR(100) NULL,                -- nama kabupaten / kota
    district    VARCHAR(100) NULL,                -- nama kecamatan
    rt          VARCHAR(10)  NULL,                -- RT (disimpan, tidak dipublikasikan)
    rw          VARCHAR(10)  NULL,                -- RW (disimpan, tidak dipublikasikan)
    website     VARCHAR(255) NULL,                -- link demo/portofolio (jasa)
    stack       VARCHAR(150) NULL,                -- teknologi (jasa)
    status      ENUM('available','sold','inactive') NOT NULL DEFAULT 'available',
    moderation  ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_listing_seller (seller_id),
    KEY idx_listing_cat (category_id),
    KEY idx_listing_browse (moderation, status, type),
    FULLTEXT KEY ft_listing_search (title, description),
    CONSTRAINT fk_listing_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_listing_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Paket harga jasa (Basic / Pro / Enterprise) ──────────────
CREATE TABLE IF NOT EXISTS listing_packages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    listing_id   INT NOT NULL,
    name         VARCHAR(60) NOT NULL,
    price        DECIMAL(15,2) NOT NULL,
    duration_days INT NULL,                       -- estimasi pengerjaan
    revisions    INT NULL,                        -- jumlah revisi
    features     TEXT NULL,                       -- daftar fitur (satu per baris)
    sort_order   TINYINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_pkg_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Foto listing ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS listing_images (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_url  VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_img_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Brief custom (buyer minta penawaran jasa) ────────────────
CREATE TABLE IF NOT EXISTS briefs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id    INT NOT NULL,
    seller_id   INT NULL,                        -- diarahkan ke seller tertentu (opsional)
    title       VARCHAR(150) NOT NULL,
    requirements TEXT NOT NULL,
    budget_min  DECIMAL(15,2) NULL,
    budget_max  DECIMAL(15,2) NULL,
    deadline    DATE NULL,
    quote_price DECIMAL(15,2) NULL,              -- penawaran harga dari seller
    status      ENUM('pending','quoted','accepted','rejected','closed') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_brief_buyer (buyer_id),
    KEY idx_brief_seller (seller_id),
    CONSTRAINT fk_brief_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_brief_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pesanan (transaksi manual: bayar → upload bukti → verifikasi) ──
CREATE TABLE IF NOT EXISTS orders (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    order_code    VARCHAR(20) NOT NULL,
    buyer_id      INT NOT NULL,
    seller_id     INT NOT NULL,
    listing_id    INT NULL,
    package_id    INT NULL,
    brief_id      INT NULL,
    title         VARCHAR(150) NOT NULL,          -- snapshot judul yang dibeli
    total         DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(20) NULL,              -- QRIS / DANA / BCA / ...
    payment_proof VARCHAR(255) NULL,              -- file upload bukti bayar
    shipping_address TEXT NULL,                   -- alamat (produk fisik)
    status        ENUM('menunggu_bukti','diverifikasi','proses','selesai','batal')
                  NOT NULL DEFAULT 'menunggu_bukti',
    notes         TEXT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_order_code (order_code),
    KEY idx_order_buyer (buyer_id),
    KEY idx_order_seller (seller_id),
    KEY idx_order_status (status),
    CONSTRAINT fk_order_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL,
    CONSTRAINT fk_order_package FOREIGN KEY (package_id) REFERENCES listing_packages(id) ON DELETE SET NULL,
    CONSTRAINT fk_order_brief FOREIGN KEY (brief_id) REFERENCES briefs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Review (1 order = 1 review, hanya setelah selesai) ───────
CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    buyer_id   INT NOT NULL,
    seller_id  INT NOT NULL,
    listing_id INT NULL,
    rating     TINYINT NOT NULL,                 -- 1..5
    comment    TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,                    -- buyer mengedit ulasan (label "diedit")
    reply      TEXT NULL,                        -- balasan penjual
    replied_at DATETIME NULL,
    reply_updated_at DATETIME NULL,
    UNIQUE KEY uq_review_order (order_id),
    CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Favorit ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS favorites (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    listing_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fav (user_id, listing_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Laporan listing & ulasan ─────────────────────────────────
CREATE TABLE IF NOT EXISTS reports (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    listing_id  INT NULL,                       -- NULL bila laporan menyorot ulasan saja
    review_id   INT NULL,                       -- laporan atas ulasan (FK reviews)
    reason      VARCHAR(255) NOT NULL,
    status      ENUM('pending','resolved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report_status (status),
    KEY idx_report_review (review_id),
    UNIQUE KEY uq_report_review (reporter_id, review_id),
    CONSTRAINT fk_report_user FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pengaturan situs (info pembayaran, kontak, dst.) ─────────
CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(60) PRIMARY KEY,
    `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed kategori ────────────────────────────────────────────
INSERT IGNORE INTO categories (name, slug, type) VALUES
    ('Jasa Web',             'jasa-web',             'service'),
    ('Elektronik',           'elektronik',           'product'),
    ('Fashion',              'fashion',              'product'),
    ('Buku',                 'buku',                 'product'),
    ('Furnitur',             'furnitur',             'product'),
    ('Peralatan Rumah Tangga','peralatan-rumah-tangga','product'),
    ('Hobi',                 'hobi',                 'product'),
    ('Kendaraan',            'kendaraan',            'product'),
    ('Aksesoris',            'aksesoris',            'product'),
    ('Lainnya',              'lainnya',              'all');

-- ── Seed pengaturan pembayaran ───────────────────────────────
INSERT IGNORE INTO settings (`key`, `value`) VALUES
    ('payment_wa',          '6287867851779'),
    ('payment_qris',        'qr.jpeg'),
    ('payment_ewallet_num', '0878-6785-1779'),
    ('payment_ewallet_name','SESSIONS STUDIO'),
    ('payment_bca',         '1234567890'),
    ('payment_bni',         '0987654321'),
    ('payment_bri',         '1122334455'),
    ('payment_bank_name',   'a.n. Nama Kamu');

<?php
include 'database.php';
include 'includes/functions.php';

// ── Listing unggulan dari database ──
$featured_services = [];
$featured_products = [];
$categories = [];
$about_stats = ['seller' => 0, 'listing' => 0, 'selesai' => 0, 'kategori' => 0];
$reviews_home = [];

try {
    $featured_services = db_all(
        $db,
        'SELECT l.id, l.title, l.price, l.type, l.status, l.moderation, c.name AS category_name,
                st.name AS subtype_name, br.name AS brand_name,
                COALESCE(sp.store_name, u.username) AS store_name, img.image_url AS image
         FROM listings l
         LEFT JOIN categories c ON c.id = l.category_id
         LEFT JOIN listing_subtypes st ON st.id = l.subtype_id
         LEFT JOIN listing_brands br ON br.id = l.brand_id
         LEFT JOIN users u ON u.id = l.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE l.moderation = "approved" AND l.status <> "inactive" AND l.type = "service"
         ORDER BY l.created_at DESC LIMIT 4'
    );
    $featured_products = db_all(
        $db,
        'SELECT l.id, l.title, l.price, l.type, l.status, l.moderation, c.name AS category_name,
                st.name AS subtype_name, br.name AS brand_name,
                COALESCE(sp.store_name, u.username) AS store_name, img.image_url AS image
         FROM listings l
         LEFT JOIN categories c ON c.id = l.category_id
         LEFT JOIN listing_subtypes st ON st.id = l.subtype_id
         LEFT JOIN listing_brands br ON br.id = l.brand_id
         LEFT JOIN users u ON u.id = l.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE l.moderation = "approved" AND l.status <> "inactive" AND l.type = "product"
         ORDER BY l.created_at DESC LIMIT 4'
    );
    $categories = db_all($db, 'SELECT name, slug FROM categories ORDER BY id LIMIT 8');

    // ── Statistik "Tentang Kami" ──
    $row = db_one($db, "SELECT COUNT(*) AS c FROM users WHERE role = 'seller' AND status = 'active'");
    $about_stats['seller'] = (int)($row['c'] ?? 0);
    $row = db_one($db, "SELECT COUNT(*) AS c FROM listings WHERE moderation = 'approved' AND status <> 'inactive'");
    $about_stats['listing'] = (int)($row['c'] ?? 0);
    $row = db_one($db, "SELECT COUNT(*) AS c FROM orders WHERE status = 'selesai'");
    $about_stats['selesai'] = (int)($row['c'] ?? 0);
    $row = db_one($db, 'SELECT COUNT(*) AS c FROM categories');
    $about_stats['kategori'] = (int)($row['c'] ?? 0);

    // ── Ulasan terbaru pembeli (sembunyi otomatis bila kosong) ──
    $reviews_home = db_all(
        $db,
        'SELECT r.rating, r.comment, r.reply, r.created_at, u.username AS buyer_name, l.title AS listing_title
         FROM reviews r
         JOIN orders o ON o.id = r.order_id
         JOIN users u ON u.id = o.buyer_id
         JOIN listings l ON l.id = o.listing_id
         ORDER BY r.created_at DESC LIMIT 6'
    );
} catch (Throwable $e) {
    // Skema belum dimigrasi — tampilkan section statis saja
}

function empty_slot(string $text, string $cta, string $href): string
{
    return '<div class="empty" style="grid-column:1/-1;">'
        . '<p>' . e($text) . '</p>'
        . '<a href="' . e($href) . '" class="btn btn-soft btn-sm mt-2">' . e($cta) . '</a>'
        . '</div>';
}

$page_title = 'Marketplace Jasa Web & Produk';
$active = 'home';
include 'includes/header.php';
?>

<main>

    <!-- HERO: pendek, langsung ke search -->
    <section class="hero">
        <div class="container">
            <span class="eyebrow">Marketplace Jasa Web &amp; Produk</span>
            <h1>Butuh website? Atau lagi cari produk?</h1>
            <p class="lead">Pesan jasa web development dari developer terpercaya, atau temukan produk pilihan
                dengan harga jelas &mdash; semuanya dalam satu tempat.</p>

            <form class="search-bar" action="listings.php" method="get" role="search">
                <input class="input" type="search" name="q" placeholder="Cari: landing page, laptop, furnitur..."
                       aria-label="Cari listing">
                <button class="btn btn-primary" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                    Cari
                </button>
            </form>

            <div class="chips">
                <a class="chip active" href="listings.php">Semua</a>
                <?php foreach ($categories as $c): ?>
                    <a class="chip" href="listings.php?cat=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- JASA WEB -->
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="section-tag">Jasa</span>
                    <h2>Jasa Web Unggulan</h2>
                </div>
                <a href="listings.php?type=service">Lihat semua</a>
            </div>

            <div class="grid">
                <?php if ($featured_services): ?>
                    <?php foreach ($featured_services as $l) { echo listing_card($l); } ?>
                <?php else: ?>
                    <?= empty_slot('Belum ada jasa web yang tayang. Jadilah penjual pertama!', 'Jual Jasa Web', 'become-seller.php') ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- PRODUK -->
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="section-tag">Produk</span>
                    <h2>Produk Pilihan</h2>
                </div>
                <a href="listings.php?type=product">Lihat semua</a>
            </div>

            <div class="grid">
                <?php if ($featured_products): ?>
                    <?php foreach ($featured_products as $l) { echo listing_card($l); } ?>
                <?php else: ?>
                    <?= empty_slot('Belum ada produk di katalog. Ajukan diri jadi seller dan mulai berjualan!', 'Jadi Penjual', 'become-seller.php') ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- TENTANG KAMI (termasuk keunggulan — tanpa kotak) -->
    <section class="section">
        <div class="container">
            <div class="about-grid">
                <div>
                    <span class="section-tag">Tentang Kami</span>
                    <h2>Jasa web &amp; produk dalam satu tempat</h2>
                    <p class="about-lead mt-2">SESSIONS mempertemukan kamu dengan developer terpercaya dan penjual
                        terverifikasi &mdash; harga transparan, proses pesan manual yang jelas, dan komunikasi
                        langsung tanpa perantara.</p>

                    <div class="about-points">
                        <div class="feature">
                            <span class="mi-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/>
                                </svg>
                            </span>
                            <div>
                                <div class="mi-title">Penjual Terkurasi</div>
                                <div class="mi-desc">Setiap seller lolos approval admin sebelum bisa jualan.</div>
                            </div>
                        </div>

                        <div class="feature">
                            <span class="mi-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </span>
                            <div>
                                <div class="mi-title">Harga Transparan</div>
                                <div class="mi-desc">Paket jasa dan harga produk tertera jelas, tanpa biaya tersembunyi.</div>
                            </div>
                        </div>

                        <div class="feature">
                            <span class="mi-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 1 1 21 12z"/>
                                </svg>
                            </span>
                            <div>
                                <div class="mi-title">Komunikasi Langsung</div>
                                <div class="mi-desc">Chat penjual via WhatsApp &mdash; deal cepat tanpa perantara.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stats-open">
                    <div>
                        <div class="stat-value"><?= $about_stats['seller'] ?></div>
                        <div class="stat-label">Seller Aktif</div>
                    </div>
                    <div>
                        <div class="stat-value"><?= $about_stats['listing'] ?></div>
                        <div class="stat-label">Listing Tayang</div>
                    </div>
                    <div>
                        <div class="stat-value"><?= $about_stats['selesai'] ?></div>
                        <div class="stat-label">Pesanan Selesai</div>
                    </div>
                    <div>
                        <div class="stat-value"><?= $about_stats['kategori'] ?></div>
                        <div class="stat-label">Kategori</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ULASAN (track horizontal — otomatis sembunyi bila belum ada) -->
    <?php if ($reviews_home): ?>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <span class="section-tag">Ulasan</span>
                        <h2>Kata pembeli</h2>
                    </div>
                </div>

                <div class="testi-track">
                    <?php foreach ($reviews_home as $r):
                        $stars = str_repeat('★', max(1, min(5, (int)$r['rating'])))
                               . str_repeat('☆', max(0, 5 - max(1, (int)$r['rating'])));
                        $initial = strtoupper(substr($r['buyer_name'], 0, 1));
                    ?>
                        <article class="testi-card">
                            <div class="testi-stars" aria-label="<?= (int)$r['rating'] ?> dari 5"><?= $stars ?></div>
                            <blockquote>&ldquo;<?= e($r['comment']) ?>&rdquo;</blockquote>
                            <?php if (!empty($r['reply'])): ?>
                                <div class="review-reply mt-1" style="font-size:13px;">
                                    <strong style="font-size:12px;">Balasan penjual:</strong>
                                    <?= e($r['reply']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="testi-who">
                                <div class="testi-avatar" aria-hidden="true"><?= e($initial) ?></div>
                                <div>
                                    <div class="testi-name"><?= e($r['buyer_name']) ?></div>
                                    <div class="testi-meta"><?= e($r['listing_title']) ?></div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- PENAWARAN EKSKLUSIF (band full-width) -->
    <section class="band">
        <div class="container band-inner">
            <div>
                <span class="section-tag">Penawaran Eksklusif</span>
                <h2>Promo mingguan jasa web &amp; produk pilihan</h2>
                <p>Jelajahi penawaran terbaik dari penjual terverifikasi &mdash; mulai dari landing page sampai
                    kebutuhan harian, harganya jelas di depan.</p>
            </div>
            <a href="listings.php" class="btn btn-primary btn-pill">
                Lihat Penawaran
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
            </a>
        </div>
    </section>

    <!-- CTA SELLER (terbuka) -->
    <section class="section">
        <div class="container center">
            <span class="section-tag" style="justify-content:center;">Gabung</span>
            <h2>Punya jasa atau produk untuk dijual?</h2>
            <p class="mt-1">Daftar sekarang, ajukan listingmu, dan mulai dapat pembeli.</p>
            <div class="mt-3" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="register.php" class="btn btn-primary">Daftar Gratis</a>
                <a href="contact.html" class="btn btn-ghost">Hubungi Kami</a>
            </div>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>

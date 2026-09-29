<?php
include 'database.php';
include 'includes/functions.php';

// ── Listing unggulan dari database ──
$featured_services = [];
$featured_products = [];
$categories = [];

try {
    $featured_services = db_all(
        $db,
        'SELECT l.id, l.title, l.price, l.type, l.status, l.moderation, c.name AS category_name,
                COALESCE(sp.store_name, u.username) AS store_name, img.image_url AS image
         FROM listings l
         LEFT JOIN categories c ON c.id = l.category_id
         LEFT JOIN users u ON u.id = l.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE l.moderation = "approved" AND l.status <> "inactive" AND l.type = "service"
         ORDER BY l.created_at DESC LIMIT 4'
    );
    $featured_products = db_all(
        $db,
        'SELECT l.id, l.title, l.price, l.type, l.status, l.moderation, c.name AS category_name,
                COALESCE(sp.store_name, u.username) AS store_name, img.image_url AS image
         FROM listings l
         LEFT JOIN categories c ON c.id = l.category_id
         LEFT JOIN users u ON u.id = l.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE l.moderation = "approved" AND l.status <> "inactive" AND l.type = "product"
         ORDER BY l.created_at DESC LIMIT 4'
    );
    $categories = db_all($db, 'SELECT name, slug FROM categories ORDER BY id LIMIT 8');
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
                <h2>Jasa Web Unggulan</h2>
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
                <h2>Produk Pilihan</h2>
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

    <!-- KEUNGGULAN -->
    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Kenapa di SESSIONS?</h2>
            </div>

            <div class="grid-2">
                <div class="panel">
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
                </div>

                <div class="panel">
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
                </div>

                <div class="panel">
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

                <div class="panel">
                    <div class="feature">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="3"/><path d="M4 12h16M12 4v16"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Jasa &amp; Produk dalam Satu Tempat</div>
                            <div class="mi-desc">Dari bikin website sampai beli barang harian, cukup di sini.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="section">
        <div class="container">
            <div class="panel center">
                <h2>Punya jasa atau produk untuk dijual?</h2>
                <p class="mt-1">Daftar sekarang, ajukan listingmu, dan mulai dapat pembeli.</p>
                <div class="mt-3" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="register.php" class="btn btn-primary">Daftar Gratis</a>
                    <a href="contact.html" class="btn btn-ghost">Hubungi Kami</a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>

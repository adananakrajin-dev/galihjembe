<?php
include 'database.php';
include 'includes/functions.php';

// ── Parameter filter ──
$q    = trim($_GET['q'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$type = in_array($_GET['type'] ?? '', ['product', 'service'], true) ? $_GET['type'] : '';
$sort = $_GET['sort'] ?? 'terbaru';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

// ── Susun WHERE ──
$where  = ["l.moderation = 'approved'", "l.status <> 'inactive'"];
$types  = '';
$args   = [];

if ($q !== '') {
    $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
    $types  .= 'ss';
    $args[]   = '%' . $q . '%';
    $args[]   = '%' . $q . '%';
}
if ($cat !== '') {
    $where[] = 'c.slug = ?';
    $types  .= 's';
    $args[]   = $cat;
}
if ($type !== '') {
    $where[] = 'l.type = ?';
    $types  .= 's';
    $args[]   = $type;
}

$where_sql = implode(' AND ', $where);

$order_sql = match ($sort) {
    'termurah' => 'l.price ASC',
    'termahal' => 'l.price DESC',
    default    => 'l.created_at DESC',
};

$from = "FROM listings l
         LEFT JOIN categories c    ON c.id = l.category_id
         LEFT JOIN users u         ON u.id = l.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id AND sp.approval = 'approved'
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE " . $where_sql;

// Total data (untuk pagination)
$total = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) " . $from);
    if ($types !== '') { $stmt->bind_param($types, ...$args); }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_row()[0];
} catch (Throwable $e) {
    // Skema belum dimigrasi — tampilkan empty state, bukan error fatal
}

$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$items = [];
if ($total > 0) {
    $sql = "SELECT l.id, l.title, l.price, l.type, l.status, l.moderation,
                   c.name AS category_name,
                   COALESCE(sp.store_name, u.username) AS store_name,
                   img.image_url AS image
            " . $from . " ORDER BY " . $order_sql . " LIMIT ? OFFSET ?";
    try {
        $stmt = $db->prepare($sql);
        $bind = $types . 'ii';
        $vals = array_merge($args, [$per_page, $offset]);
        $stmt->bind_param($bind, ...$vals);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) {
        $items = [];
    }
}

// ── Kategori untuk chips ──
$categories = [];
try { $categories = db_all($db, 'SELECT name, slug FROM categories ORDER BY id'); }
catch (Throwable $e) { $categories = []; }

// Helper URL (pertahankan semua param kecuali page)
function listings_url(array $override): string {
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'listings.php' . ($params ? '?' . http_build_query($params) : '');
}

$page_title = $q ? 'Cari: ' . $q : 'Katalog';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1><?= $type === 'service' ? 'Jasa Web' : ($type === 'product' ? 'Produk' : 'Katalog') ?></h1>
            <p><?= $q !== '' ? 'Hasil pencarian untuk <strong style="color:var(--text)">' . e($q) . '</strong> — ' : '' ?>
               <?= (int)$total ?> listing ditemukan.</p>

            <!-- Search + sort -->
            <form class="search-bar mt-3" action="listings.php" method="get" role="search" style="margin-left:0;margin-right:0;">
                <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari jasa atau produk..." aria-label="Cari">
                <?php if ($cat): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
                <?php if ($type): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
                <button class="btn btn-primary" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                    Cari
                </button>
            </form>

            <!-- Chips kategori -->
            <div class="chips mt-2" style="justify-content:flex-start;">
                <a class="chip <?= $cat === '' ? 'active' : '' ?>" href="<?= e(listings_url(['cat' => '', 'page' => ''])) ?>">Semua</a>
                <?php foreach ($categories as $c): ?>
                    <a class="chip <?= $cat === $c['slug'] ? 'active' : '' ?>"
                       href="<?= e(listings_url(['cat' => $c['slug'], 'page' => ''])) ?>"><?= e($c['name']) ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Sort + tipe -->
            <div class="mt-2" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <a class="btn btn-sm <?= $type === '' ? 'btn-primary' : 'btn-soft' ?>"
                   href="<?= e(listings_url(['type' => '', 'page' => ''])) ?>">Semua Tipe</a>
                <a class="btn btn-sm <?= $type === 'service' ? 'btn-primary' : 'btn-soft' ?>"
                   href="<?= e(listings_url(['type' => 'service', 'page' => ''])) ?>">Jasa</a>
                <a class="btn btn-sm <?= $type === 'product' ? 'btn-primary' : 'btn-soft' ?>"
                   href="<?= e(listings_url(['type' => 'product', 'page' => ''])) ?>">Produk</a>

                <form method="get" action="listings.php" style="margin-left:auto;display:flex;gap:8px;">
                    <?php foreach ($_GET as $k => $v): ?>
                        <?php if ($k !== 'sort' && $k !== 'page'): ?>
                            <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <select name="sort" class="input" style="min-height:36px;padding:6px 10px;font-size:13px;width:auto;" onchange="this.form.submit()">
                        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="termurah" <?= $sort === 'termurah' ? 'selected' : '' ?>>Harga Termurah</option>
                        <option value="termahal" <?= $sort === 'termahal' ? 'selected' : '' ?>>Harga Termahal</option>
                    </select>
                </form>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container">
            <?php flash_alert(); ?>

            <?php if (!$items): ?>
                <div class="empty">
                    <h3>Produk tidak ditemukan</h3>
                    <p>Coba kata kunci lain, ganti kategori, atau lihat semua listing.</p>
                    <a href="listings.php" class="btn btn-primary mt-3">Lihat Semua Listing</a>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($items as $l) { echo listing_card($l); } ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="mt-4" style="display:flex;gap:10px;justify-content:center;align-items:center;flex-wrap:wrap;">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-soft btn-sm" href="<?= e(listings_url(['page' => $page - 1])) ?>">← Sebelumnya</a>
                        <?php endif; ?>
                        <span class="faint">Halaman <?= $page ?> dari <?= $total_pages ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a class="btn btn-soft btn-sm" href="<?= e(listings_url(['page' => $page + 1])) ?>">Berikutnya →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

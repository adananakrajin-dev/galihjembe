<?php
include 'database.php';
include 'includes/functions.php';

// ── Parameter filter ──
$q    = trim($_GET['q'] ?? '');
$cat  = trim($_GET['cat'] ?? '');
$type = in_array($_GET['type'] ?? '', ['product', 'service'], true) ? $_GET['type'] : '';
$sort = $_GET['sort'] ?? 'terbaru';
$prov = trim($_GET['prov'] ?? '');
$kab  = trim($_GET['kab'] ?? '');
$sub   = (int)($_GET['sub'] ?? 0);    // filter subtipe (id listing_subtypes)
$brand = (int)($_GET['brand'] ?? 0);  // filter merek (id listing_brands)
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
if ($prov !== '') {
    $where[] = 'l.province = ?';
    $types  .= 's';
    $args[]   = $prov;
}
if ($kab !== '') {
    $where[] = 'l.regency = ?';
    $types  .= 's';
    $args[]   = $kab;
}

// ── Validasi filter subtipe & merek (harus nyambung ke kategori / satu sama lain) ──
$cat_id = 0;
if ($cat !== '') {
    try {
        $crow = db_one($db, 'SELECT id FROM categories WHERE slug = ?', 's', $cat);
        $cat_id = $crow ? (int)$crow['id'] : 0;
    } catch (Throwable $e) { $cat_id = 0; }
}
if ($sub > 0) {
    try { $srow = db_one($db, 'SELECT id, category_id FROM listing_subtypes WHERE id = ?', 'i', $sub); }
    catch (Throwable $e) { $srow = null; }
    if (!$srow || ($cat_id > 0 && (int)$srow['category_id'] !== $cat_id)) { $sub = 0; }
}
if ($brand > 0) {
    try {
        $brow = db_one(
            $db,
            'SELECT b.id, b.subtype_id, s.category_id
             FROM listing_brands b
             JOIN listing_subtypes s ON s.id = b.subtype_id
             WHERE b.id = ?', 'i', $brand
        );
    } catch (Throwable $e) { $brow = null; }
    $brand_ok = $brow
        && ($sub <= 0 || (int)$brow['subtype_id'] === $sub)
        && ($cat_id <= 0 || (int)$brow['category_id'] === $cat_id);
    if (!$brand_ok) { $brand = 0; }
}
if ($sub > 0) {
    $where[] = 'l.subtype_id = ?';
    $types  .= 'i';
    $args[]   = $sub;
}
if ($brand > 0) {
    $where[] = 'l.brand_id = ?';
    $types  .= 'i';
    $args[]   = $brand;
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
         LEFT JOIN listing_subtypes st ON st.id = l.subtype_id
         LEFT JOIN listing_brands br   ON br.id = l.brand_id
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
                   st.name AS subtype_name, br.name AS brand_name,
                   COALESCE(sp.store_name, u.username) AS store_name,
                   u.avatar AS seller_avatar,
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

// ── Opsi filter subtipe & merek (muncul saat kategori dipilih) ──
$subtypes_opts = [];
$brands_opts   = [];
if ($cat_id > 0) {
    try {
        $subtypes_opts = db_all($db, 'SELECT id, name FROM listing_subtypes WHERE category_id = ? ORDER BY name', 'i', $cat_id);
        $brands_opts = $sub > 0
            ? db_all($db, 'SELECT b.id, b.name FROM listing_brands b WHERE b.subtype_id = ? ORDER BY b.name', 'i', $sub)
            : db_all($db, 'SELECT b.id, b.name FROM listing_brands b
                           JOIN listing_subtypes s ON s.id = b.subtype_id
                           WHERE s.category_id = ? ORDER BY b.name', 'i', $cat_id);
    } catch (Throwable $e) { $subtypes_opts = []; $brands_opts = []; }
}

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
                <?php if ($prov): ?><input type="hidden" name="prov" value="<?= e($prov) ?>"><?php endif; ?>
                <?php if ($kab): ?><input type="hidden" name="kab" value="<?= e($kab) ?>"><?php endif; ?>
                <?php if ($sub): ?><input type="hidden" name="sub" value="<?= $sub ?>"><?php endif; ?>
                <?php if ($brand): ?><input type="hidden" name="brand" value="<?= $brand ?>"><?php endif; ?>
                <button class="btn btn-primary" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                    Cari
                </button>
            </form>

            <!-- Chips kategori -->
            <div class="chips mt-2" style="justify-content:flex-start;">
                <a class="chip <?= $cat === '' ? 'active' : '' ?>" href="<?= e(listings_url(['cat' => '', 'page' => '', 'sub' => '', 'brand' => ''])) ?>">Semua</a>
                <?php foreach ($categories as $c): ?>
                    <a class="chip <?= $cat === $c['slug'] ? 'active' : '' ?>"
                       href="<?= e(listings_url(['cat' => $c['slug'], 'page' => '', 'sub' => '', 'brand' => ''])) ?>"><?= e($c['name']) ?></a>
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

                <div id="katalogLocError" style="width:100%;"></div>
                <form method="get" action="listings.php" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
                    <?php foreach ($_GET as $k => $v): ?>
                        <?php if (!in_array($k, ['sort', 'page', 'prov', 'kab', 'sub', 'brand'], true)): ?>
                            <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($subtypes_opts): ?>
                        <select name="sub" class="input"
                                style="min-height:36px;padding:6px 10px;font-size:13px;width:auto;max-width:180px;"
                                onchange="var b=this.form.elements.brand; if(b){b.value='';} this.form.submit()">
                            <option value="">Semua Tipe Produk</option>
                            <?php foreach ($subtypes_opts as $so): ?>
                                <option value="<?= (int)$so['id'] ?>" <?= $sub === (int)$so['id'] ? 'selected' : '' ?>><?= e($so['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <?php if ($brands_opts): ?>
                        <select name="brand" class="input"
                                style="min-height:36px;padding:6px 10px;font-size:13px;width:auto;max-width:180px;"
                                onchange="this.form.submit()">
                            <option value="">Semua Merek</option>
                            <?php foreach ($brands_opts as $bo): ?>
                                <option value="<?= (int)$bo['id'] ?>" <?= $brand === (int)$bo['id'] ? 'selected' : '' ?>><?= e($bo['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <div data-wilayah="katalogLocError" data-wilayah-submit="change"
                         data-wilayah-ids="f_province,f_regency" style="display:flex;gap:8px;flex-wrap:wrap;">
                        <select id="f_province" name="prov" class="input"
                                style="min-height:36px;padding:6px 10px;font-size:13px;width:auto;max-width:180px;"
                                data-placeholder="Semua Lokasi" data-selected="<?= e($prov) ?>"></select>
                        <select id="f_regency" name="kab" class="input"
                                style="min-height:36px;padding:6px 10px;font-size:13px;width:auto;max-width:180px;"
                                data-placeholder="Semua Kab/Kota" data-selected="<?= e($kab) ?>"></select>
                    </div>
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

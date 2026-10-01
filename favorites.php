<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

// ── Aksi: lepas favorit ──
if (isset($_POST['lepas'])) {
    csrf_check();
    $fid = (int)($_POST['fav_id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM favorites WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $fid, $user_id);
    $stmt->execute();
    $stmt->close();
    set_flash('Dihapus dari favorit.', 'info');
    header('Location: favorites.php');
    exit;
}

$items = [];
try {
    $items = db_all(
        $db,
        'SELECT f.id AS fav_id, l.id, l.title, l.price, l.type, l.status, l.moderation,
                c.name AS category_name, st.name AS subtype_name, br.name AS brand_name,
                COALESCE(sp.store_name, u.username) AS store_name,
                img.image_url AS image
         FROM favorites f
         JOIN listings l ON l.id = f.listing_id
         LEFT JOIN categories c ON c.id = l.category_id
         LEFT JOIN listing_subtypes st ON st.id = l.subtype_id
         LEFT JOIN listing_brands br ON br.id = l.brand_id
         LEFT JOIN users u ON u.id = l.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE f.user_id = ?
         ORDER BY f.created_at DESC',
        'i', $user_id
    );
} catch (Throwable $e) {
    $items = [];
}

$page_title = 'Favorit';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Favorit Saya</h1>
            <p>Listing yang kamu simpan untuk dilihat lagi nanti.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:18px;">
        <div class="container">
            <?php if (!$items): ?>
                <div class="empty">
                    <h3>Belum ada favorit</h3>
                    <p>Ketuk tombol favorit di halaman listing untuk menyimpannya di sini.</p>
                    <a href="listings.php" class="btn btn-primary mt-3">Jelajahi Katalog</a>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($items as $l): ?>
                        <div style="position:relative;">
                            <?= listing_card($l) ?>
                            <form method="POST" action="favorites.php"
                                  data-confirm="Lepas &quot;<?= e($l['title']) ?>&quot; dari favorit?"
                                  style="position:absolute;top:8px;right:8px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="fav_id" value="<?= (int)$l['fav_id'] ?>">
                                <button type="submit" name="lepas" aria-label="Lepas favorit"
                                        style="width:36px;height:36px;border-radius:50%;border:1px solid var(--border);
                                               background:rgba(7,8,12,.82);color:var(--danger);cursor:pointer;
                                               display:flex;align-items:center;justify-content:center;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5">
                                        <path d="M12 20s-7-4.5-7-9.5A3.8 3.8 0 0 1 12 7a3.8 3.8 0 0 1 7 3.5C19 15.5 12 20 12 20z"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

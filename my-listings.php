<?php
include 'database.php';
include 'includes/functions.php';
require_role('seller', 'admin');

$seller_id = (int)$_SESSION['user_id'];

// ── Aksi: hapus listing ──
if (isset($_POST['hapus'])) {
    csrf_check();
    $del_id = (int)($_POST['listing_id'] ?? 0);
    $own = db_one($db, 'SELECT id FROM listings WHERE id = ? AND seller_id = ?', 'ii', $del_id, $seller_id);
    if ($own) {
        // Kumpulkan file gambar dulu untuk dihapus dari disk
        try {
            foreach (db_all($db, 'SELECT image_url FROM listing_images WHERE listing_id = ?', 'i', $del_id) as $im) {
                delete_upload($im['image_url']);
            }
        } catch (Throwable $e) {}
        $stmt = $db->prepare('DELETE FROM listings WHERE id = ?'); // cascade: images & packages
        $stmt->bind_param('i', $del_id);
        $stmt->execute();
        $stmt->close();
        set_flash('Listing berhasil dihapus.', 'success');
    } else {
        set_flash('Listing tidak ditemukan.', 'danger');
    }
    header('Location: my-listings.php');
    exit;
}

// ── Aksi: toggle status aktif/nonaktif ──
if (isset($_POST['toggle_status'])) {
    csrf_check();
    $tid = (int)($_POST['listing_id'] ?? 0);
    $own = db_one($db, 'SELECT id, status FROM listings WHERE id = ? AND seller_id = ?', 'ii', $tid, $seller_id);
    if ($own) {
        $new = $own['status'] === 'inactive' ? 'available' : 'inactive';
        $stmt = $db->prepare('UPDATE listings SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $new, $tid);
        $stmt->execute();
        $stmt->close();
        set_flash($new === 'inactive' ? 'Listing dinonaktifkan.' : 'Listing diaktifkan kembali.', 'success');
    }
    header('Location: my-listings.php');
    exit;
}

// ── Data listing milik saya ──
$items = [];
try {
    $items = db_all(
        $db,
        'SELECT l.id, l.title, l.price, l.type, l.status, l.moderation, l.created_at,
                c.name AS category_name, img.image_url AS image,
                (SELECT COUNT(*) FROM orders o WHERE o.listing_id = l.id AND o.status <> "batal") AS order_count
         FROM listings l
         LEFT JOIN categories c ON c.id = l.category_id
         LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1
         WHERE l.seller_id = ?
         ORDER BY l.created_at DESC',
        'i', $seller_id
    );
} catch (Throwable $e) {
    $items = [];
}

$page_title = 'Listing Saya';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;">
                <div>
                    <h1>Listing Saya</h1>
                    <p>Kelola jasa web &amp; produk yang kamu jual.</p>
                </div>
                <a href="listing-form.php" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah Listing
                </a>
            </div>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:18px;">
        <div class="container">
            <?php if (!$items): ?>
                <div class="empty">
                    <h3>Belum ada listing</h3>
                    <p>Mulai jual — buat listing jasa web atau produk pertamamu. Setelah dibuat, listing perlu moderasi singkat oleh admin.</p>
                    <a href="listing-form.php" class="btn btn-primary mt-3">Buat Listing Pertama</a>
                </div>
            <?php else: ?>
                <div class="row-list">
                    <?php foreach ($items as $l):
                        $mod_badge = match ($l['moderation']) {
                            'approved' => '<span class="badge badge-available">Disetujui</span>',
                            'rejected' => '<span class="badge badge-sold">Ditolak</span>',
                            default    => '<span class="badge badge-pending">Menunggu Moderasi</span>',
                        };
                    ?>
                        <div class="row-item">
                            <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
                                <div class="card-media" style="width:70px;height:70px;flex:none;aspect-ratio:1;border-radius:10px;">
                                    <?php if ($l['image']): ?>
                                        <img src="uploads/<?= e($l['image']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <?= $l['type'] === 'service' ? ICON_SERVICE : ICON_PRODUCT ?>
                                    <?php endif; ?>
                                </div>

                                <div style="flex:1;min-width:190px;">
                                    <a href="listing-detail.php?id=<?= (int)$l['id'] ?>" style="text-decoration:none;">
                                        <strong style="font-size:15px;color:var(--text);"><?= e($l['title']) ?></strong>
                                    </a>
                                    <div class="card-price" style="margin-top:2px;"><?= rupiah($l['price']) ?></div>
                                    <div class="card-meta">
                                        <span class="badge <?= $l['type'] === 'service' ? 'badge-info' : 'badge-muted' ?>"><?= $l['type'] === 'service' ? 'Jasa' : 'Produk' ?></span>
                                        <?= $mod_badge ?>
                                        <?php if ($l['status'] === 'sold'): ?>
                                            <span class="badge badge-sold">Terjual</span>
                                        <?php elseif ($l['status'] === 'inactive'): ?>
                                            <span class="badge badge-muted">Nonaktif</span>
                                        <?php endif; ?>
                                        <?php if ($l['category_name']): ?><span><?= e($l['category_name']) ?></span><?php endif; ?>
                                        <span><?= (int)$l['order_count'] ?> pesanan</span>
                                    </div>
                                </div>

                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                    <a class="btn btn-soft btn-sm" href="listing-form.php?id=<?= (int)$l['id'] ?>">Edit</a>
                                    <form method="POST" action="my-listings.php" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-ghost btn-sm">
                                            <?= $l['status'] === 'inactive' ? 'Aktifkan' : 'Nonaktifkan' ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="my-listings.php" data-confirm="Hapus listing &quot;<?= e($l['title']) ?>&quot; beserta foto dan paketnya? Tindakan ini tidak bisa dibatalkan."
                                          style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

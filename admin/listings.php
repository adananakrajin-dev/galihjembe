<?php
$page_title  = 'Moderasi Listing';
$admin_active = 'listings';
include '_head.php';

$filter = $_GET['f'] ?? 'pending';
$allowed = ['pending', 'approved', 'rejected', 'all'];

// ── Aksi moderasi ──
if (isset($_POST['aksi'])) {
    csrf_check();
    $lid = (int)($_POST['listing_id'] ?? 0);
    $act = $_POST['act'] ?? '';
    if (in_array($act, ['approve', 'reject'], true) && $lid > 0) {
        $mod = $act === 'approve' ? 'approved' : 'rejected';
        $stmt = $db->prepare('UPDATE listings SET moderation = ? WHERE id = ?');
        $stmt->bind_param('si', $mod, $lid);
        $stmt->execute();
        $stmt->close();
        set_flash($act === 'approve' ? 'Listing disetujui dan tayang.' : 'Listing ditolak.', 'success');
    }
    header('Location: listings.php?f=' . e($filter));
    exit;
}

$items = [];
try {
    $sql = 'SELECT l.*, u.username AS seller_username, COALESCE(sp.store_name, u.username) AS store_name,
                   c.name AS category_name, img.image_url AS image
            FROM listings l
            JOIN users u ON u.id = l.seller_id
            LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
            LEFT JOIN categories c ON c.id = l.category_id
            LEFT JOIN listing_images img ON img.listing_id = l.id AND img.is_primary = 1';
    if (in_array($filter, $allowed, true) && $filter !== 'all') {
        $items = db_all($db, $sql . ' WHERE l.moderation = ? ORDER BY l.created_at DESC', 's', $filter);
    } else {
        $filter = 'all';
        $items = db_all($db, $sql . ' ORDER BY CASE l.moderation WHEN \'pending\' THEN 0 WHEN \'approved\' THEN 1 WHEN \'rejected\' THEN 2 ELSE 3 END, l.created_at DESC');
    }
} catch (Throwable $e) {
    $items = [];
}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Moderasi Listing</h1>
            <p>Tinjau listing baru sebelum tayang di katalog.</p>

            <div class="chips mt-2" style="justify-content:flex-start;">
                <?php foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $k => $lbl): ?>
                    <a class="chip <?= $filter === $k ? 'active' : '' ?>" href="listings.php?f=<?= $k ?>"><?= $lbl ?></a>
                <?php endforeach; ?>
            </div>

            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if (!$items): ?>
                <div class="empty">
                    <h3>Tidak ada listing</h3>
                    <p>Tidak ada listing dengan status filter ini.</p>
                </div>
            <?php else: ?>
                <div class="row-list">
                    <?php foreach ($items as $l):
                        $mod_badge = match ($l['moderation']) {
                            'approved' => '<span class="badge badge-available">Disetujui</span>',
                            'rejected' => '<span class="badge badge-sold">Ditolak</span>',
                            default    => '<span class="badge badge-pending">Menunggu</span>',
                        };
                    ?>
                        <div class="row-item">
                            <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
                                <div class="card-media" style="width:66px;height:66px;flex:none;aspect-ratio:1;border-radius:10px;">
                                    <?php if ($l['image']): ?>
                                        <img src="../uploads/<?= e($l['image']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <?= $l['type'] === 'service' ? ICON_SERVICE : ICON_PRODUCT ?>
                                    <?php endif; ?>
                                </div>

                                <div style="flex:1;min-width:200px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <a href="../listing-detail.php?id=<?= (int)$l['id'] ?>" target="_blank">
                                            <strong style="font-size:15px;"><?= e($l['title']) ?></strong>
                                        </a>
                                        <?= $mod_badge ?>
                                        <span class="badge <?= $l['type'] === 'service' ? 'badge-info' : 'badge-muted' ?>">
                                            <?= $l['type'] === 'service' ? 'Jasa' : 'Produk' ?>
                                        </span>
                                    </div>
                                    <div class="card-price" style="margin-top:3px;"><?= rupiah($l['price']) ?></div>
                                    <div class="faint" style="margin-top:3px;">
                                        <?= e($l['store_name']) ?> (@<?= e($l['seller_username']) ?>) ·
                                        <?= e($l['category_name'] ?: 'tanpa kategori') ?> ·
                                        <?= e(date('d M Y H:i', strtotime($l['created_at']))) ?>
                                        <?= $l['status'] === 'sold' ? ' · TERJUAL' : '' ?>
                                    </div>
                                    <p class="mt-1" style="font-size:13.5px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        <?= e($l['description']) ?>
                                    </p>
                                </div>

                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <?php if ($l['moderation'] !== 'approved'): ?>
                                        <form method="POST" data-confirm="Setujui listing ini?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                            <input type="hidden" name="act" value="approve">
                                            <button type="submit" name="aksi" class="btn btn-primary btn-sm">Setujui</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($l['moderation'] !== 'rejected'): ?>
                                        <form method="POST" data-confirm="Tolak listing ini?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                                            <input type="hidden" name="act" value="reject">
                                            <button type="submit" name="aksi" class="btn btn-danger btn-sm">Tolak</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '_foot.php'; ?>

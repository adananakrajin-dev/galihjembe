<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$buyer_id = (int)$_SESSION['user_id'];
$filter   = $_GET['status'] ?? '';
$allowed  = ['menunggu_bukti', 'diverifikasi', 'proses', 'selesai', 'batal'];

$orders = [];
try {
    if (in_array($filter, $allowed, true)) {
        $orders = db_all(
            $db,
            'SELECT o.*, COALESCE(sp.store_name, u.username) AS store_name, li.image AS image
             FROM orders o
             LEFT JOIN users u ON u.id = o.seller_id
             LEFT JOIN seller_profiles sp ON sp.user_id = o.seller_id
             LEFT JOIN (
                 SELECT listing_id, MIN(image_url) AS image
                 FROM listing_images GROUP BY listing_id
             ) li ON li.listing_id = o.listing_id
             WHERE o.buyer_id = ? AND o.status = ?
             ORDER BY o.created_at DESC',
            'is', $buyer_id, $filter
        );
    } else {
        $orders = db_all(
            $db,
            'SELECT o.*, COALESCE(sp.store_name, u.username) AS store_name, li.image AS image
             FROM orders o
             LEFT JOIN users u ON u.id = o.seller_id
             LEFT JOIN seller_profiles sp ON sp.user_id = o.seller_id
             LEFT JOIN (
                 SELECT listing_id, MIN(image_url) AS image
                 FROM listing_images GROUP BY listing_id
             ) li ON li.listing_id = o.listing_id
             WHERE o.buyer_id = ?
             ORDER BY o.created_at DESC',
            'i', $buyer_id
        );
    }
} catch (Throwable $e) {
    $orders = [];
}

$page_title = 'Pesanan Saya';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Pesanan Saya</h1>
            <p>Pantau status pembayaran dan pengerjaan pesananmu di sini.</p>

            <div class="chips mt-2" style="justify-content:flex-start;">
                <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="orders.php">Semua</a>
                <a class="chip <?= $filter === 'menunggu_bukti' ? 'active' : '' ?>" href="orders.php?status=menunggu_bukti">Menunggu Bayar</a>
                <a class="chip <?= $filter === 'proses' ? 'active' : '' ?>" href="orders.php?status=proses">Diproses</a>
                <a class="chip <?= $filter === 'selesai' ? 'active' : '' ?>" href="orders.php?status=selesai">Selesai</a>
                <a class="chip <?= $filter === 'batal' ? 'active' : '' ?>" href="orders.php?status=batal">Dibatalkan</a>
            </div>

            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:18px;">
        <div class="container">
            <?php if (!$orders): ?>
                <div class="empty">
                    <h3>Belum ada pesanan</h3>
                    <p>Yuk mulai belanja — temukan jasa web atau produk favoritmu di katalog.</p>
                    <a href="listings.php" class="btn btn-primary mt-3">Buka Katalog</a>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($orders as $o):
                        $can_pay = $o['status'] === 'menunggu_bukti' && empty($o['payment_proof']);
                    ?>
                        <div class="panel" style="padding:18px 20px;">
                            <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                                <!-- Thumbnail -->
                                <div class="card-media" style="width:74px;height:74px;flex:none;aspect-ratio:1;border-radius:10px;">
                                    <?php if ($o['image']): ?>
                                        <img src="uploads/<?= e($o['image']) ?>" alt="" loading="lazy">
                                    <?php elseif ($o['brief_id']): ?>
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 4h10l4 4v12H5z"/><path d="M15 4v4h4M8 13h8M8 17h5"/></svg>
                                    <?php else: ?>
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 7h12l1 12H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                                    <?php endif; ?>
                                </div>

                                <!-- Info -->
                                <div style="flex:1;min-width:200px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <strong style="font-size:15px;"><?= e($o['order_code']) ?></strong>
                                        <?= order_status_badge($o) ?>
                                    </div>
                                    <div class="card-title" style="margin-top:4px;"><?= e($o['title']) ?></div>
                                    <div class="faint">
                                        Penjual: <?= e($o['store_name']) ?> ·
                                        <?= e(date('d M Y H:i', strtotime($o['created_at']))) ?>
                                        <?php if ($o['payment_method']): ?> · <?= e($o['payment_method']) ?><?php endif; ?>
                                    </div>
                                </div>

                                <!-- Total + aksi -->
                                <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                                    <div class="card-price"><?= rupiah($o['total']) ?></div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <?php if ($can_pay): ?>
                                            <a class="btn btn-primary btn-sm" href="checkout.php?order=<?= (int)$o['id'] ?>">Bayar Sekarang</a>
                                        <?php endif; ?>
                                        <a class="btn btn-soft btn-sm" href="order-detail.php?id=<?= (int)$o['id'] ?>">Detail</a>
                                    </div>
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

<?php
include 'database.php';
include 'includes/functions.php';
require_role('seller', 'admin');

$seller_id = (int)$_SESSION['user_id'];

// ── Aksi pesanan ──
if (isset($_POST['aksi_pesanan'])) {
    csrf_check();
    $oid = (int)($_POST['order_id'] ?? 0);
    $act = $_POST['act'] ?? '';
    $o = db_one($db, 'SELECT * FROM orders WHERE id = ? AND seller_id = ?', 'ii', $oid, $seller_id);

    if (!$o) {
        set_flash('Pesanan tidak ditemukan.', 'danger');
    } else {
        $ok = false;
        if ($act === 'proses' && $o['status'] === 'diverifikasi') {
            $ok = $db->query("UPDATE orders SET status = 'proses' WHERE id = " . $oid);
        } elseif ($act === 'selesai' && $o['status'] === 'proses') {
            $ok = $db->query("UPDATE orders SET status = 'selesai' WHERE id = " . $oid);
            // Produk fisik otomatis ditandai terjual
            if ($ok && $o['listing_id']) {
                $db->query("UPDATE listings SET status = 'sold' WHERE id = " . (int)$o['listing_id'] . " AND type = 'product'");
            }
        } elseif ($act === 'batal' && in_array($o['status'], ['menunggu_bukti', 'diverifikasi'], true)) {
            $ok = $db->query("UPDATE orders SET status = 'batal' WHERE id = " . $oid);
        }

        if ($ok) {
            set_flash(
                match ($act) {
                    'proses' => 'Pesanan diproses. Kerjakan sebaik mungkin ya!',
                    'selesai' => 'Pesanan ditandai selesai. Pembeli bisa memberi ulasan.',
                    'batal' => 'Pesanan dibatalkan.',
                },
                'success'
            );
        } else {
            set_flash('Aksi tidak valid untuk status pesanan saat ini.', 'danger');
        }
    }
    header('Location: seller-orders.php');
    exit;
}

// ── Aksi: kirim penawaran brief ──
if (isset($_POST['kirim_penawaran'])) {
    csrf_check();
    $bid = (int)($_POST['brief_id'] ?? 0);
    $quote = (float)str_replace([',', '.'], '', $_POST['quote_price'] ?? '0');

    $b = db_one(
        $db,
        'SELECT * FROM briefs WHERE id = ? AND status = "pending" AND (seller_id IS NULL OR seller_id = ?)',
        'ii', $bid, $seller_id
    );

    if (!$b) {
        set_flash('Brief sudah diambil penjual lain atau tidak tersedia.', 'danger');
    } elseif ($quote <= 0) {
        set_flash('Masukkan harga penawaran yang valid.', 'danger');
    } else {
        $stmt = $db->prepare('UPDATE briefs SET seller_id = ?, quote_price = ?, status = "quoted" WHERE id = ?');
        $stmt->bind_param('idi', $seller_id, $quote, $bid);
        $stmt->execute();
        $stmt->close();
        set_flash('Penawaran terkirim ke pembeli.', 'success');
    }
    header('Location: seller-orders.php');
    exit;
}

// ── Data pesanan masuk ──
$orders = [];
try {
    $orders = db_all(
        $db,
        'SELECT o.*, ub.name AS buyer_name, ub.username AS buyer_username
         FROM orders o JOIN users ub ON ub.id = o.buyer_id
         WHERE o.seller_id = ?
         ORDER BY FIELD(o.status, "menunggu_bukti", "diverifikasi", "proses", "selesai", "batal"), o.created_at DESC',
        'i', $seller_id
    );
} catch (Throwable $e) {}

// ── Data brief masuk (ditujukan ke saya atau terbuka) ──
$briefs = [];
try {
    $briefs = db_all(
        $db,
        'SELECT b.*, ub.name AS buyer_name, ub.username AS buyer_username
         FROM briefs b JOIN users ub ON ub.id = b.buyer_id
         WHERE (b.seller_id = ? OR b.seller_id IS NULL)
           AND b.status <> "accepted"
         ORDER BY FIELD(b.status, "pending", "quoted"), b.created_at DESC',
        'i', $seller_id
    );
} catch (Throwable $e) {}

$page_title = 'Pesanan Masuk';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Pesanan Masuk</h1>
            <p>Kelola pesanan pembeli dan penawaran brief yang masuk ke tokomu.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <!-- Pesanan -->
    <section class="section" style="padding-top:8px;">
        <div class="container">
            <div class="section-head"><h2>Pesanan (<?= count($orders) ?>)</h2></div>

            <?php if (!$orders): ?>
                <div class="empty">
                    <h3>Belum ada pesanan</h3>
                    <p>Pastikan listingmu tampil di katalog — pembeli akan datang memesan.</p>
                    <a href="my-listings.php" class="btn btn-soft mt-3">Kelola Listing</a>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($orders as $o):
                        $can = [
                            'proses' => $o['status'] === 'diverifikasi',
                            'selesai' => $o['status'] === 'proses',
                            'batal' => in_array($o['status'], ['menunggu_bukti', 'diverifikasi'], true),
                        ];
                    ?>
                        <div class="panel" style="padding:18px 20px;">
                            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;">
                                <div style="flex:1;min-width:220px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <strong><?= e($o['order_code']) ?></strong>
                                        <?= order_status_badge($o) ?>
                                        <?php if ($o['brief_id']): ?><span class="badge badge-info">Brief</span><?php endif; ?>
                                    </div>
                                    <div class="card-title" style="margin-top:5px;"><?= e($o['title']) ?></div>
                                    <div class="faint">
                                        Pembeli: <?= e($o['buyer_name'] ?: $o['buyer_username']) ?> ·
                                        <?= e(date('d M Y H:i', strtotime($o['created_at']))) ?>
                                        <?php if ($o['payment_method']): ?> · <?= e($o['payment_method']) ?><?php endif; ?>
                                    </div>
                                    <?php if ($o['notes']): ?>
                                        <p class="mt-1" style="font-size:13.5px;">Catatan: <?= e($o['notes']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($o['shipping_address']): ?>
                                        <p class="mt-1" style="font-size:13.5px;">Alamat: <?= e($o['shipping_address']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div style="text-align:right;display:flex;flex-direction:column;gap:10px;align-items:flex-end;">
                                    <div class="card-price"><?= rupiah($o['total']) ?></div>

                                    <?php if ($o['payment_proof']): ?>
                                        <a class="btn btn-ghost btn-sm" href="uploads/<?= e($o['payment_proof']) ?>" target="_blank">
                                            Lihat Bukti Bayar
                                        </a>
                                    <?php endif; ?>

                                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                        <?php if ($can['proses']): ?>
                                            <form method="POST" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="act" value="proses">
                                                <button type="submit" name="aksi_pesanan" class="btn btn-primary btn-sm">Mulai Proses</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($can['selesai']): ?>
                                            <form method="POST" style="display:inline;"
                                                  data-confirm="Tandai pesanan ini selesai? Pembeli bisa memberi ulasan.">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="act" value="selesai">
                                                <button type="submit" name="aksi_pesanan" class="btn btn-primary btn-sm">Tandai Selesai</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($can['batal']): ?>
                                            <form method="POST" style="display:inline;"
                                                  data-confirm="Batalkan pesanan ini?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="act" value="batal">
                                                <button type="submit" name="aksi_pesanan" class="btn btn-danger btn-sm">Batalkan</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($o['status'] === 'menunggu_bukti' && !$o['payment_proof']): ?>
                                        <span class="faint" style="color:var(--warning);">Menunggu pembeli bayar</span>
                                    <?php elseif ($o['status'] === 'menunggu_bukti'): ?>
                                        <span class="faint" style="color:var(--warning);">Menunggu verifikasi admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Brief masuk -->
    <section class="section">
        <div class="container">
            <div class="section-head"><h2>Brief Masuk (<?= count($briefs) ?>)</h2></div>

            <?php if (!$briefs): ?>
                <div class="empty">
                    <h3>Belum ada brief</h3>
                    <p>Brief custom dari pembeli akan muncul di sini untuk kamu beri penawaran.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($briefs as $b): ?>
                        <div class="panel" style="padding:18px 20px;">
                            <div style="display:flex;gap:12px;justify-content:space-between;flex-wrap:wrap;align-items:flex-start;">
                                <div style="flex:1;min-width:220px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <strong><?= e($b['title']) ?></strong>
                                        <?php if ($b['seller_id'] == $seller_id): ?>
                                            <span class="badge badge-info">Untukmu</span>
                                        <?php else: ?>
                                            <span class="badge badge-muted">Brief Terbuka</span>
                                        <?php endif; ?>
                                        <span class="badge <?= $b['status'] === 'quoted' ? 'badge-info' : 'badge-pending' ?>">
                                            <?= $b['status'] === 'quoted' ? 'Sudah Dijawab' : 'Menunggu Penawaran' ?>
                                        </span>
                                    </div>
                                    <div class="faint" style="margin-top:4px;">
                                        Dari <?= e($b['buyer_name'] ?: $b['buyer_username']) ?> ·
                                        <?= e(date('d M Y', strtotime($b['created_at']))) ?>
                                        <?php if ($b['budget_min'] || $b['budget_max']): ?>
                                            · budget
                                            <?= $b['budget_min'] ? rupiah($b['budget_min']) : '?' ?>
                                            –
                                            <?= $b['budget_max'] ? rupiah($b['budget_max']) : '?' ?>
                                        <?php endif; ?>
                                        <?php if ($b['deadline']): ?> · sebelum <?= e(date('d M Y', strtotime($b['deadline']))) ?><?php endif; ?>
                                    </div>
                                    <p class="mt-1" style="font-size:13.5px;white-space:pre-line;"><?= e($b['requirements']) ?></p>
                                </div>

                                <div style="min-width:230px;">
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" action="seller-orders.php" class="form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="brief_id" value="<?= (int)$b['id'] ?>">
                                            <div class="field">
                                                <label for="quote<?= (int)$b['id'] ?>">Harga Penawaran (Rp)</label>
                                                <input id="quote<?= (int)$b['id'] ?>" type="text" name="quote_price"
                                                       inputmode="numeric" required placeholder="3500000">
                                            </div>
                                            <button type="submit" name="kirim_penawaran" class="btn btn-primary btn-sm btn-block">
                                                Kirim Penawaran
                                            </button>
                                        </form>
                                    <?php elseif ($b['status'] === 'quoted' && $b['seller_id'] == $seller_id): ?>
                                        <div class="order-summary">
                                            <div class="faint">Penawaranmu</div>
                                            <div class="card-price"><?= rupiah($b['quote_price']) ?></div>
                                            <div class="faint mt-1">Menunggu keputusan pembeli.</div>
                                        </div>
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

<?php include 'includes/footer.php'; ?>

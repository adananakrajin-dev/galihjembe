<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$buyer_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);

try {
    $order = db_one(
        $db,
        'SELECT o.*, COALESCE(sp.store_name, u.username) AS store_name, u.phone AS seller_phone,
                l.description AS listing_desc, l.location AS listing_location
         FROM orders o
         LEFT JOIN users u ON u.id = o.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = o.seller_id
         LEFT JOIN listings l ON l.id = o.listing_id
         WHERE o.id = ? AND o.buyer_id = ?',
        'ii', $order_id, $buyer_id
    );
} catch (Throwable $e) {
    $order = null;
}

if (!$order) {
    $page_title = 'Pesanan Tidak Ditemukan';
    include 'includes/header.php';
    echo '<main><section class="section"><div class="container"><div class="empty">'
       . '<h3>Pesanan tidak ditemukan</h3><p>Pesanan mungkin milik akun lain atau sudah dihapus.</p>'
       . '<a href="orders.php" class="btn btn-primary mt-3">Kembali ke Pesanan</a>'
       . '</div></div></section></main>';
    include 'includes/footer.php';
    exit;
}

// ── Aksi: kirim review (hanya setelah selesai) ──
$has_review = null;
if (isset($_POST['kirim_review'])) {
    csrf_check();
    if ($order['status'] !== 'selesai') {
        set_flash('Review hanya bisa diberikan setelah pesanan selesai.', 'danger');
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5) {
            set_flash('Pilih rating bintang 1–5.', 'danger');
        } else {
            try {
                $stmt = $db->prepare(
                    'INSERT INTO reviews (order_id, buyer_id, seller_id, listing_id, rating, comment)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $oid = (int)$order['id'];
                $sid = (int)$order['seller_id'];
                $lid = !empty($order['listing_id']) ? (int)$order['listing_id'] : null; // pesanan brief tanpa listing
                $stmt->bind_param('iiiiis', $oid, $buyer_id, $sid, $lid, $rating, $comment);
                $stmt->execute();
                $stmt->close();
                set_flash('Terima kasih! Ulasanmu terkirim.', 'success');
            } catch (Throwable $e) {
                set_flash('Ulasan sudah diberikan untuk pesanan ini.', 'info');
            }
        }
    }
    header('Location: order-detail.php?id=' . $order_id);
    exit;
}

try { $has_review = db_one($db, 'SELECT * FROM reviews WHERE order_id = ?', 'i', $order_id); }
catch (Throwable $e) { $has_review = null; }

// ── Langkah status (timeline sederhana) ──
$status_rank = ['menunggu_bukti' => 1, 'diverifikasi' => 2, 'proses' => 3, 'selesai' => 4];
$rank = $order['status'] === 'batal' ? 0 : ($status_rank[$order['status']] ?? 1);
$steps = [
    ['Pesanan dibuat', 'Kode pesanan diterbitkan'],
    ['Pembayaran', 'Transfer/QRIS + upload bukti'],
    ['Verifikasi', 'Admin memeriksa bukti bayar'],
    ['Pengerjaan', 'Penjual memproses pesanan'],
    ['Selesai', 'Pesanan rampung & bisa direview'],
];

$page_title = 'Pesanan ' . $order['order_code'];
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <a class="back-link" href="orders.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                Kembali ke pesanan saya
            </a>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:6px;">
                <h1 style="margin:0;"><?= e($order['order_code']) ?></h1>
                <?= order_status_badge($order) ?>
            </div>
            <p>Dibuat <?= e(date('d M Y H:i', strtotime($order['created_at']))) ?> · Penjual: <?= e($order['store_name']) ?></p>

            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <div class="grid-2" style="align-items:start;">

                <!-- Kiri: detail + timeline -->
                <div>
                    <div>
                        <h3 class="mb-2">Detail Pesanan</h3>
                        <div class="order-summary">
                            <div class="summary-row" style="color:var(--text);font-weight:600;">
                                <span><?= e($order['title']) ?></span>
                                <?php if ($order['brief_id']): ?>
                                    <span class="badge badge-info">Brief</span>
                                <?php elseif ($order['package_id']): ?>
                                    <span class="badge badge-info">Paket Jasa</span>
                                <?php endif; ?>
                            </div>
                            <div class="summary-row"><span>Total</span><span style="color:var(--accent-strong);font-weight:700;"><?= rupiah($order['total']) ?></span></div>
                            <div class="summary-row"><span>Metode Bayar</span><span><?= e($order['payment_method'] ?: 'Belum dipilih') ?></span></div>
                            <?php if ($order['shipping_address']): ?>
                                <div class="summary-row" style="flex-direction:column;gap:4px;">
                                    <span>Alamat Kirim</span>
                                    <span style="color:var(--text);text-align:left;"><?= e($order['shipping_address']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['notes']): ?>
                                <div class="summary-row" style="flex-direction:column;gap:4px;">
                                    <span>Catatan</span>
                                    <span style="color:var(--text);text-align:left;"><?= e($order['notes']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($order['payment_proof']): ?>
                            <div class="divider"></div>
                            <div class="faint mb-1">Bukti pembayaran</div>
                            <a href="uploads/<?= e($order['payment_proof']) ?>" target="_blank">
                                <div class="card-media" style="max-width:220px;aspect-ratio:4/3;border-radius:10px;">
                                    <img src="uploads/<?= e($order['payment_proof']) ?>" alt="Bukti pembayaran" style="object-fit:contain;background:#fff;">
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <h3 class="mb-2">Status Pesanan</h3>
                        <?php if ($order['status'] === 'batal'): ?>
                            <div class="alert alert-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                                <span>Pesanan ini dibatalkan.</span>
                            </div>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:0;">
                                <?php foreach ($steps as $i => $step):
                                    $done = $i <= $rank || ($i === 4 && $rank >= 4);
                                    $current = $i === $rank && !($i === 1 && $order['payment_proof'] && $rank === 1 && false);
                                ?>
                                    <div style="display:flex;gap:12px;align-items:flex-start;">
                                        <div style="display:flex;flex-direction:column;align-items:center;">
                                            <span style="width:22px;height:22px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;
                                                background:<?= $done ? 'var(--success-soft)' : 'var(--surface-3)' ?>;
                                                border:1px solid <?= $done ? 'rgba(52,211,153,.5)' : 'var(--border)' ?>;
                                                color:<?= $done ? 'var(--success)' : 'var(--faint)' ?>;font-size:11px;font-weight:700;">
                                                <?= $done ? '&#10003;' : $i + 1 ?>
                                            </span>
                                            <?php if ($i < count($steps) - 1): ?>
                                                <span style="width:2px;height:30px;background:<?= $i < $rank ? 'var(--success)' : 'var(--border)' ?>;"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="padding-bottom:10px;">
                                            <div style="font-size:14px;font-weight:600;color:<?= $done ? 'var(--text)' : 'var(--faint)' ?>;"><?= e($step[0]) ?></div>
                                            <div class="faint"><?= e($step[1]) ?></div>
                                            <?php if ($i === 1 && $rank === 1): ?>
                                                <div class="faint" style="color:var(--warning);">
                                                    <?= $order['payment_proof'] ? 'Bukti terkirim — menunggu verifikasi admin' : 'Belum ada bukti pembayaran' ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kanan: aksi pembayaran / review -->
                <div>
                    <?php if ($order['status'] === 'menunggu_bukti'): ?>
                        <div>
                            <h3 class="mb-2">Pembayaran</h3>
                            <?php if (!$order['payment_proof']): ?>
                                <p class="faint mb-2">Belum ada bukti pembayaran. Pilih metode & upload bukti dulu.</p>
                                <a href="checkout.php?order=<?= (int)$order['id'] ?>" class="btn btn-primary btn-block">
                                    Bayar Sekarang
                                </a>
                            <?php else: ?>
                                <div class="alert alert-info mb-2">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                                    <span>Bukti terkirim, menunggu verifikasi. Bisa ganti bukti bila ada kesalahan.</span>
                                </div>
                                <a href="checkout.php?order=<?= (int)$order['id'] ?>" class="btn btn-soft btn-block">Kelola Pembayaran</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['status'] === 'selesai'): ?>
                        <div class="mt-3">
                            <h3 class="mb-2">Beri Ulasan</h3>
                            <?php if ($has_review): ?>
                                <div class="alert alert-success">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
                                    <span>Terima kasih, ulasanmu sudah terkirim (<?= (int)$has_review['rating'] ?>/5).</span>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="order-detail.php?id=<?= $order_id ?>" class="form">
                                    <?= csrf_field() ?>
                                    <div class="field">
                                        <label>Rating</label>
                                        <div style="display:flex;gap:6px;" id="starPick">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <label style="cursor:pointer;">
                                                    <input type="radio" name="rating" value="<?= $s ?>" required style="position:absolute;opacity:0;">
                                                    <span class="star" data-v="<?= $s ?>" style="font-size:26px;color:var(--faint);transition:color .15s;">&#9733;</span>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="comment">Komentar <span class="faint">(opsional)</span></label>
                                        <textarea id="comment" name="comment" style="min-height:90px;" placeholder="Ceritakan pengalamanmu..."></textarea>
                                    </div>
                                    <button type="submit" name="kirim_review" class="btn btn-primary btn-block">Kirim Ulasan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['payment_method'] || $order['status'] !== 'menunggu_bukti'): ?>
                        <div class="mt-3">
                            <div class="feature">
                                <span class="mi-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 1 1 21 12z"/></svg>
                                </span>
                                <div>
                                    <div class="mi-title">Ada masalah dengan pesanan?</div>
                                    <div class="mi-desc">Hubungi admin lewat halaman kontak.</div>
                                    <a class="btn btn-soft btn-sm mt-2" href="contact.html">Hubungi Kami</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
</main>

<script>
(function () {
    var wrap = document.getElementById('starPick');
    if (!wrap) return;
    var stars = wrap.querySelectorAll('.star');
    function paint(v) {
        stars.forEach(function (s) {
            s.style.color = (+s.dataset.v <= v) ? 'var(--warning)' : 'var(--faint)';
        });
    }
    stars.forEach(function (s) {
        s.addEventListener('mouseenter', function () { paint(+s.dataset.v); });
        s.addEventListener('click', function () { paint(+s.dataset.v); });
    });
    wrap.addEventListener('mouseleave', function () {
        var checked = wrap.querySelector('input:checked');
        paint(checked ? +checked.value : 0);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>

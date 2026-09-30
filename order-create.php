<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$listing_id = (int)($_GET['listing'] ?? $_POST['listing'] ?? 0);
$package_id = (int)($_GET['package'] ?? $_POST['package'] ?? 0);
$brief_id   = (int)($_GET['brief'] ?? $_POST['brief'] ?? 0);
$buyer_id   = (int)$_SESSION['user_id'];

// ── Mode brief: harga dari penawaran seller ──
$brief  = null;
$pkg    = null;

try {
    if ($brief_id > 0) {
        $brief = db_one($db, 'SELECT * FROM briefs WHERE id = ? AND buyer_id = ?', 'ii', $brief_id, $buyer_id);
        if (!$brief || $brief['status'] !== 'quoted' || !$brief['quote_price']) {
            set_flash('Brief ini belum memiliki penawaran harga yang bisa dipesan.', 'danger');
            header('Location: brief.php');
            exit;
        }
        $listing_id = 0;
    }
} catch (Throwable $e) {
    set_flash('Terjadi kesalahan data. Coba lagi.', 'danger');
    header('Location: listings.php');
    exit;
}

$listing = null;
try {
    if ($listing_id > 0) {
        $listing = db_one($db, 'SELECT * FROM listings WHERE id = ?', 'i', $listing_id);
        if (!$listing) { throw new RuntimeException('not found'); }

        if ((int)$listing['seller_id'] === $buyer_id) {
            set_flash('Kamu tidak bisa membeli listing milikmu sendiri.', 'danger');
            header('Location: listing-detail.php?id=' . $listing_id);
            exit;
        }
        if ($listing['moderation'] !== 'approved' || $listing['status'] === 'inactive') {
            set_flash('Listing ini belum bisa dipesan.', 'danger');
            header('Location: listing-detail.php?id=' . $listing_id);
            exit;
        }
        if ($listing['status'] === 'sold') {
            set_flash('Maaf, listing ini sudah terjual.', 'danger');
            header('Location: listing-detail.php?id=' . $listing_id);
            exit;
        }

        if ($package_id > 0) {
            $pkg = db_one($db, 'SELECT * FROM listing_packages WHERE id = ? AND listing_id = ?', 'ii', $package_id, $listing_id);
            if (!$pkg) { $package_id = 0; }
        }
    } elseif (!$brief) {
        // Tidak ada listing maupun brief → sumber pesanan tidak valid
        throw new RuntimeException('no source');
    }
} catch (Throwable $e) {
    set_flash('Listing tidak ditemukan.', 'danger');
    header('Location: listings.php');
    exit;
}

// ── Sumber pesanan ──
if ($brief) {
    $title     = $brief['title'];
    $seller_id = (int)$brief['seller_id'];
    $base      = (float)$brief['quote_price'];
    $subtitle  = 'Penawaran brief custom';
} else {
    $title     = $listing['title'];
    $seller_id = (int)$listing['seller_id'];
    $base      = $pkg ? (float)$pkg['price'] : (float)$listing['price'];
    $subtitle  = $pkg
        ? 'Paket ' . $pkg['name'] . ($pkg['duration_days'] ? ' · ' . $pkg['duration_days'] . ' hari' : '')
        : ($listing['type'] === 'service' ? 'Jasa' : 'Produk');
}

$ppn   = (int)round($base * 0.11);
$total = $base + $ppn;
$is_product = $brief ? false : ($listing['type'] === 'product');

// ── Proses buat pesanan ──
if (isset($_POST['buat_order'])) {
    csrf_check();

    $address = trim($_POST['address'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');

    if ($is_product && $address === '') {
        $error = 'Alamat pengiriman wajib diisi untuk produk fisik.';
    } else {
        // Kode order unik: ORD-YYMMDD-XXXX
        $order_code = '';
        for ($i = 0; $i < 5; $i++) {
            $order_code = 'ORD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            if (!db_one($db, 'SELECT id FROM orders WHERE order_code = ?', 's', $order_code)) { break; }
        }

        // ID opsional harus NULL (bukan 0) agar lolos foreign key
        $listing_bind = $listing_id > 0 ? $listing_id : null;
        $package_bind = $package_id > 0 ? $package_id : null;
        $brief_bind   = $brief_id   > 0 ? $brief_id   : null;

        $stmt = $db->prepare(
            'INSERT INTO orders (order_code, buyer_id, seller_id, listing_id, package_id, brief_id,
                                 title, total, shipping_address, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "menunggu_bukti")'
        );
        $stmt->bind_param(
            'siiiiisdss',   // s code, i buyer, i seller, i listing, i package, i brief, s title, d total, s address, s notes
            $order_code, $buyer_id, $seller_id,
            $listing_bind, $package_bind, $brief_bind,
            $title, $total, $address, $notes
        );

        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();

            if ($brief) {
                $stmt = $db->prepare('UPDATE briefs SET status = "accepted" WHERE id = ?');
                $stmt->bind_param('i', $brief_id);
                $stmt->execute();
                $stmt->close();
            }

            set_flash('Pesanan dibuat! Selesaikan pembayaran agar pesanan diproses.', 'success');
            header('Location: checkout.php?order=' . $new_id);
            exit;
        }
        $stmt->close();
        $error = 'Gagal membuat pesanan, coba lagi.';
    }
}

$page_title = 'Buat Pesanan';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <a class="back-link" href="<?= $listing ? 'listing-detail.php?id=' . $listing_id : 'brief.php' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                Kembali
            </a>
            <h1>Buat Pesanan</h1>
            <p>Periksa detail pesananmu sebelum melanjutkan ke instruksi pembayaran.</p>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-3" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid-2" style="align-items:start;">
                <!-- Ringkasan -->
                <div>
                    <h3 class="mb-2">Ringkasan Pesanan</h3>
                    <div class="order-summary">
                        <div class="summary-row" style="color:var(--text);font-weight:600;">
                            <span><?= e($title) ?></span>
                            <span class="badge badge-info"><?= e($subtitle) ?></span>
                        </div>
                        <div class="summary-row"><span>Pemesan</span><span style="color:var(--text)"><?= e($_SESSION['username']) ?></span></div>
                        <div class="summary-row"><span>Harga</span><span><?= rupiah($base) ?></span></div>
                        <div class="summary-row"><span>PPN (11%)</span><span><?= rupiah($ppn) ?></span></div>
                        <div class="summary-row"><span>Biaya Layanan</span><span style="color:var(--success)">Gratis</span></div>
                        <div class="summary-row total"><span>Total</span><span style="color:var(--accent-strong)"><?= rupiah($total) ?></span></div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                        <span>Pembayaran manual — transfer/QRIS lalu upload bukti. Diverifikasi admin.</span>
                    </div>
                </div>

                <!-- Form -->
                <div>
                    <h3 class="mb-2">Data Pengiriman / Catatan</h3>
                    <form action="order-create.php?listing=<?= $listing_id ?>&package=<?= $package_id ?>&brief=<?= $brief_id ?>"
                          method="POST" class="form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="listing" value="<?= $listing_id ?>">
                        <input type="hidden" name="package" value="<?= $package_id ?>">
                        <input type="hidden" name="brief" value="<?= $brief_id ?>">

                        <?php if ($is_product): ?>
                            <div class="field">
                                <label for="address">Alamat Pengiriman</label>
                                <textarea id="address" name="address" required
                                          placeholder="Nama penerima, no. HP, alamat lengkap"><?= e($_POST['address'] ?? '') ?></textarea>
                                <span class="hint">Barang dikirim setelah pembayaran diverifikasi (pengiriman diurus di luar platform).</span>
                            </div>
                        <?php endif; ?>

                        <div class="field">
                            <label for="notes">Catatan untuk Penjual <span class="faint">(opsional)</span></label>
                            <textarea id="notes" name="notes" style="min-height:90px;"
                                      placeholder="<?= $is_product ? 'Warna, varian, jam kirim, dll.' : 'Revisi, warna, referensi, dll.' ?>"><?= e($_POST['notes'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" name="buat_order" class="btn btn-primary btn-block">
                            Buat Pesanan &amp; Minta Instruksi Bayar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

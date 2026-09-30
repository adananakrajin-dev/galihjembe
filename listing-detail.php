<?php
include 'database.php';
include 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$viewer_id = (int)($_SESSION['user_id'] ?? 0);

// ── Aksi POST: favorit & laporan ──
if (isset($_POST['toggle_fav'])) {
    csrf_check();
    require_login('listing-detail.php?id=' . $id);
    $exists = db_one($db, 'SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?', 'ii', $viewer_id, $id);
    if ($exists) {
        $stmt = $db->prepare('DELETE FROM favorites WHERE id = ?');
        $stmt->bind_param('i', $exists['id']);
    } else {
        $stmt = $db->prepare('INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $viewer_id, $id);
    }
    $stmt->execute();
    $stmt->close();
    header('Location: listing-detail.php?id=' . $id);
    exit;
}

if (isset($_POST['report'])) {
    csrf_check();
    require_login('listing-detail.php?id=' . $id);
    $reason = trim($_POST['reason'] ?? '');
    if ($reason !== '') {
        $stmt = $db->prepare('INSERT INTO reports (reporter_id, listing_id, reason) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $viewer_id, $id, $reason);
        $stmt->execute();
        $stmt->close();
        set_flash('Laporan terkirim. Admin akan meninjau listing ini.', 'info');
    }
    header('Location: listing-detail.php?id=' . $id);
    exit;
}

// ── Ambil listing (approved, atau milik sendiri / admin) ──
$sql = "SELECT l.*,
               c.name AS category_name,
               u.username, u.name AS seller_name, u.phone AS seller_phone,
               COALESCE(sp.store_name, u.username) AS store_name,
               sp.store_desc, sp.approval AS store_approval
        FROM listings l
        LEFT JOIN categories c ON c.id = l.category_id
        LEFT JOIN users u ON u.id = l.seller_id
        LEFT JOIN seller_profiles sp ON sp.user_id = l.seller_id
        WHERE l.id = ?";

try {
    $listing = db_one($db, $sql, 'i', $id);
} catch (Throwable $e) {
    $listing = null;
}

$is_owner = $listing && $viewer_id > 0 && (int)$listing['seller_id'] === $viewer_id;
$visible  = $listing && ($listing['moderation'] === 'approved' || $is_owner || is_admin());

if (!$visible) {
    $page_title = 'Tidak Ditemukan';
    include 'includes/header.php';
    echo '<main><section class="section"><div class="container">'
       . '<div class="empty"><h3>Listing tidak ditemukan</h3>'
       . '<p>Listing mungkin sudah dihapus, ditolak moderasi, atau salah ID.</p>'
       . '<a href="listings.php" class="btn btn-primary mt-3">Kembali ke Katalog</a></div>'
       . '</div></section></main>';
    include 'includes/footer.php';
    exit;
}

// ── Data turunan ──
$images  = [];
$packages = [];
$reviews  = [];
$avg_rating = 0;
$is_fav   = false;

try { $images  = db_all($db, 'SELECT image_url FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, id', 'i', $id); }
catch (Throwable $e) {}
try { $packages = db_all($db, 'SELECT * FROM listing_packages WHERE listing_id = ? ORDER BY sort_order, id', 'i', $id); }
catch (Throwable $e) {}
try {
    $reviews = db_all(
        $db,
        "SELECT r.rating, r.comment, r.created_at, ub.name AS buyer_name, ub.username AS buyer_username
         FROM reviews r JOIN users ub ON ub.id = r.buyer_id
         WHERE r.listing_id = ? ORDER BY r.created_at DESC LIMIT 10",
        'i', $id
    );
} catch (Throwable $e) {}

if ($reviews) {
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
}
if ($viewer_id > 0) {
    try { $is_fav = (bool)db_one($db, 'SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?', 'ii', $viewer_id, $id); }
    catch (Throwable $e) {}
}

$is_service = $listing['type'] === 'service';
$sold       = $listing['status'] === 'sold';
$can_buy    = !$sold && $listing['moderation'] === 'approved';

// Nomor WA penjual → format 62…
function wa_number(?string $phone): ?string {
    $p = preg_replace('/[^0-9]/', '', (string)$phone);
    if ($p === '') { return null; }
    if (strpos($p, '0') === 0) { $p = '62' . substr($p, 1); }
    elseif (strpos($p, '8') === 0) { $p = '62' . $p; }
    return $p;
}
$wa = wa_number($listing['seller_phone'] ?? '');

$primary_image = $images[0]['image_url'] ?? null;

$page_title = $listing['title'];
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <a class="back-link" href="listings.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                Kembali ke katalog
            </a>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
                <?php if ($listing['category_name']): ?>
                    <span class="badge badge-muted"><?= e($listing['category_name']) ?></span>
                <?php endif; ?>
                <span class="badge <?= $is_service ? 'badge-info' : 'badge-muted' ?>"><?= $is_service ? 'Jasa' : 'Produk' ?></span>
                <?php if ($listing['moderation'] === 'pending'): ?>
                    <span class="badge badge-pending">Menunggu Moderasi</span>
                <?php else: ?>
                    <?= status_badge($listing) ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top:18px;">
        <div class="container">
            <?php flash_alert(); ?>

            <div class="grid-2" style="align-items:start;">

                <!-- Kolom kiri: media + deskripsi -->
                <div>
                    <div>
                        <div class="card-media" style="border-radius:12px;aspect-ratio:16/10;">
                            <?php if ($primary_image): ?>
                                <img src="uploads/<?= e($primary_image) ?>" alt="<?= e($listing['title']) ?>">
                            <?php else: ?>
                                <?= $is_service ? ICON_SERVICE : ICON_PRODUCT ?>
                            <?php endif; ?>
                        </div>

                        <?php if (count($images) > 1): ?>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:8px;margin-top:10px;">
                                <?php foreach (array_slice($images, 1, 6) as $im): ?>
                                    <div class="card-media" style="aspect-ratio:1;border-radius:8px;">
                                        <img src="uploads/<?= e($im['image_url']) ?>" alt="" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="divider"></div>

                    <div>
                        <h3><?= e($listing['title']) ?></h3>
                        <div class="card-meta" style="margin-top:8px;">
                            <?php if ($avg_rating > 0): ?>
                                <span class="badge badge-pending">★ <?= e((string)$avg_rating) ?> (<?= count($reviews) ?> ulasan)</span>
                            <?php endif; ?>
                            <?php
                            // Lokasi publik: berhenti di kecamatan (RT/RW tidak dipublikasikan);
                            // listing lama tanpa cascade jatuh ke kolom teks `location`.
                            $loc_public = (!empty($listing['district']) && !empty($listing['regency']))
                                ? 'Kec. ' . $listing['district'] . ', ' . $listing['regency']
                                  . (!empty($listing['province']) ? ', ' . $listing['province'] : '')
                                : trim($listing['location'] ?? '');
                            ?>
                            <?php if ($loc_public !== ''): ?>
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                    <?= e($loc_public) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!$is_service && $listing['condition']): ?>
                                <span>Kondisi: <?= e($listing['condition']) ?></span>
                            <?php endif; ?>
                            <?php if ($is_service && $listing['stack']): ?>
                                <span>Teknologi: <?= e($listing['stack']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="divider"></div>

                        <p style="white-space:pre-line;"><?= e($listing['description']) ?></p>

                        <?php if ($is_service && $listing['website']): ?>
                            <p class="mt-2"><a class="btn btn-soft btn-sm" href="<?= e($listing['website']) ?>" target="_blank" rel="noopener">
                                Lihat Demo / Portofolio
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 5h5v5M19 5l-8 8M18 14v5H5V6h5"/></svg>
                            </a></p>
                        <?php endif; ?>
                    </div>

                    <!-- Paket jasa -->
                    <?php if ($is_service && $packages): ?>
                        <div class="divider"></div>

                        <div id="paket">
                            <h3 class="mb-2">Paket Layanan</h3>
                            <div class="grid-3" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                                <?php foreach ($packages as $p): ?>
                                    <div class="order-summary" style="display:flex;flex-direction:column;">
                                        <strong><?= e($p['name']) ?></strong>
                                        <div class="card-price mt-1"><?= rupiah($p['price']) ?></div>
                                        <div class="faint mt-1">
                                            <?= $p['duration_days'] ? (int)$p['duration_days'] . ' hari kerja' : 'Durasi disepakati' ?>
                                            <?= $p['revisions'] !== null ? ' · ' . (int)$p['revisions'] . '× revisi' : '' ?>
                                        </div>
                                        <?php if ($p['features']): ?>
                                            <ul class="mt-2" style="list-style:none;display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--muted);">
                                                <?php foreach (array_filter(array_map('trim', explode("\n", $p['features']))) as $f): ?>
                                                    <li style="display:flex;gap:7px;">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5" style="flex:none;margin-top:3px;"><path d="M5 13l4 4 10-10"/></svg>
                                                        <?= e($f) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <?php if ($can_buy): ?>
                                            <a class="btn btn-primary btn-sm mt-3" style="margin-top:auto;"
                                               href="order-create.php?listing=<?= (int)$listing['id'] ?>&package=<?= (int)$p['id'] ?>">
                                                Pilih Paket Ini
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Ulasan -->
                    <div class="divider"></div>

                    <div>
                        <h3 class="mb-2">Ulasan Pembeli</h3>
                        <?php if (!$reviews): ?>
                            <p class="faint">Belum ada ulasan untuk listing ini.</p>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <?php foreach ($reviews as $r): ?>
                                    <div>
                                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                            <strong style="font-size:14px;"><?= e($r['buyer_name'] ?: $r['buyer_username']) ?></strong>
                                            <span class="badge badge-pending"><?= str_repeat('★', (int)$r['rating']) ?></span>
                                            <span class="faint"><?= e(date('d M Y', strtotime($r['created_at']))) ?></span>
                                        </div>
                                        <?php if ($r['comment']): ?>
                                            <p class="mt-1" style="font-size:14px;"><?= e($r['comment']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kolom kanan: harga + aksi + penjual -->
                <div>
                    <div class="panel">
                        <div class="faint"><?= $is_service ? 'Mulai dari' : 'Harga' ?></div>
                        <div style="font-size:clamp(24px,4vw,32px);font-weight:700;color:var(--accent-strong);margin-top:4px;">
                            <?= rupiah($listing['price']) ?>
                        </div>

                        <div class="mt-3" style="display:flex;flex-direction:column;gap:10px;">
                            <?php if (!$can_buy): ?>
                                <button class="btn btn-soft btn-block" disabled><?= $sold ? 'Sudah Terjual' : 'Belum Disetujui Admin' ?></button>
                            <?php elseif ($is_service): ?>
                                <?php if ($packages): ?>
                                    <a class="btn btn-primary btn-block" href="#paket">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/></svg>
                                        Pilih Paket
                                    </a>
                                <?php else: ?>
                                    <a class="btn btn-primary btn-block" href="order-create.php?listing=<?= (int)$listing['id'] ?>">Pesan Jasa Ini</a>
                                <?php endif; ?>
                                <a class="btn btn-ghost btn-block" href="brief.php?seller=<?= (int)$listing['seller_id'] ?>">
                                    Ajukan Brief Custom
                                </a>
                            <?php else: ?>
                                <a class="btn btn-primary btn-block" href="order-create.php?listing=<?= (int)$listing['id'] ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7h12l1 12H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                                    Beli Sekarang
                                </a>
                            <?php endif; ?>

                            <?php if (is_logged()): ?>
                                <form method="POST" action="listing-detail.php?id=<?= $id ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" name="toggle_fav" class="btn btn-soft btn-block">
                                        <svg viewBox="0 0 24 24" fill="<?= $is_fav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"
                                             style="color:<?= $is_fav ? 'var(--danger)' : 'inherit' ?>">
                                            <path d="M12 20s-7-4.5-7-9.5A3.8 3.8 0 0 1 12 7a3.8 3.8 0 0 1 7 3.5C19 15.5 12 20 12 20z"/>
                                        </svg>
                                        <?= $is_fav ? 'Tersimpan di Favorit' : 'Simpan ke Favorit' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a class="btn btn-soft btn-block" href="login.php?dari=listing-detail.php?id=<?= $id ?>">
                                    Login untuk Simpan / Beli
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kartu penjual -->
                    <div class="divider"></div>

                    <div>
                        <div class="feature">
                            <span class="mi-icon" style="border-radius:50%;">
                                <?= strtoupper(substr($listing['store_name'], 0, 1)) ?>
                            </span>
                            <div>
                                <div class="mi-title"><?= e($listing['store_name']) ?></div>
                                <div class="mi-desc">
                                    <?= ($listing['store_approval'] ?? null) === 'approved' ? 'Terverifikasi' : 'Penjual' ?>
                                    <?= $listing['username'] ? ' · @' . e($listing['username']) : '' ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($listing['store_desc']): ?>
                            <p class="mt-2" style="font-size:13.5px;"><?= e($listing['store_desc']) ?></p>
                        <?php endif; ?>

                        <div class="mt-3" style="display:flex;flex-direction:column;gap:10px;">
                            <?php if ($wa): ?>
                                <a class="btn-wa" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Halo, saya tertarik dengan "' . $listing['title'] . '" di SESSIONS.') ?>"
                                   target="_blank" rel="noopener">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 1 1 21 12z"/></svg>
                                    Chat Penjual via WhatsApp
                                </a>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                                    <span>Penjual belum memasang nomor WhatsApp.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Laporan -->
                    <?php if (is_logged() && !$is_owner): ?>
                        <details class="mt-3" style="border-top:1px solid var(--border);padding-top:14px;">
                            <summary style="cursor:pointer;font-size:13px;color:var(--faint);">Laporkan listing ini</summary>
                            <form method="POST" action="listing-detail.php?id=<?= $id ?>" class="mt-2">
                                <?= csrf_field() ?>
                                <div class="field">
                                    <label for="reason">Alasan laporan</label>
                                    <textarea id="reason" name="reason" required maxlength="255" style="min-height:80px;"
                                              placeholder="Contoh: barang tidak sesuai deskripsi..."></textarea>
                                </div>
                                <button type="submit" name="report" class="btn btn-danger btn-sm mt-2">Kirim Laporan</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

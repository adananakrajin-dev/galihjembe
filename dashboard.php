<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$username = $_SESSION['username'] ?? 'Pengguna';
$role     = current_role();
$initial  = strtoupper(substr($username, 0, 1));

/** Hitung aman: kembalikan 0 bila tabel belum ada (skema belum di-migrasi). */
function try_count(mysqli $db, string $sql): int {
    try {
        $res = $db->query($sql);
        $row = $res ? $res->fetch_row() : null;
        return (int)($row[0] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

$stats = [];
if ($role === 'admin') {
    $stats = [
        ['label' => 'Total Pengguna',  'value' => try_count($db, 'SELECT COUNT(*) FROM users')],
        ['label' => 'Total Listing',   'value' => try_count($db, 'SELECT COUNT(*) FROM listings')],
        ['label' => 'Bayar Menunggu',  'value' => try_count($db, "SELECT COUNT(*) FROM orders WHERE status = 'menunggu_bukti'")],
        ['label' => 'Seller Pending',  'value' => try_count($db, "SELECT COUNT(*) FROM seller_profiles WHERE approval = 'pending'")],
    ];
} elseif ($role === 'seller') {
    $stats = [
        ['label' => 'Listing Saya',    'value' => try_count($db, 'SELECT COUNT(*) FROM listings WHERE seller_id = ' . (int)($_SESSION['user_id'] ?? 0))],
        ['label' => 'Pesanan Masuk',   'value' => try_count($db, "SELECT COUNT(*) FROM orders WHERE seller_id = " . (int)($_SESSION['user_id'] ?? 0) . " AND status IN ('menunggu_bukti','diverifikasi','proses')")],
        ['label' => 'Selesai',         'value' => try_count($db, "SELECT COUNT(*) FROM orders WHERE seller_id = " . (int)($_SESSION['user_id'] ?? 0) . " AND status = 'selesai'")],
        ['label' => 'Pendapatan',      'value' => 'Rp ' . number_format((float)(try_count($db, "SELECT COALESCE(SUM(total),0) FROM orders WHERE seller_id = " . (int)($_SESSION['user_id'] ?? 0) . " AND status = 'selesai'")), 0, ',', '.')],
    ];
} else {
    $stats = [
        ['label' => 'Pesanan Saya',    'value' => try_count($db, 'SELECT COUNT(*) FROM orders WHERE buyer_id = ' . (int)($_SESSION['user_id'] ?? 0))],
        ['label' => 'Sedang Diproses', 'value' => try_count($db, "SELECT COUNT(*) FROM orders WHERE buyer_id = " . (int)($_SESSION['user_id'] ?? 0) . " AND status IN ('diverifikasi','proses')")],
        ['label' => 'Selesai',         'value' => try_count($db, "SELECT COUNT(*) FROM orders WHERE buyer_id = " . (int)($_SESSION['user_id'] ?? 0) . " AND status = 'selesai'")],
        ['label' => 'Favorit',         'value' => try_count($db, 'SELECT COUNT(*) FROM favorites WHERE user_id = ' . (int)($_SESSION['user_id'] ?? 0))],
    ];
}

$role_badge = ['admin' => ['Admin', 'badge-info'], 'seller' => ['Penjual', 'badge-available']][$role] ?? ['Pembeli', 'badge-muted'];

$page_title = 'Dashboard';
$active = 'dashboard';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <div class="feature" style="align-items:center;">
                <span class="mi-icon" style="width:52px;height:52px;border-radius:50%;font-weight:700;font-size:20px;">
                    <?= e($initial) ?>
                </span>
                <div>
                    <h1 style="font-size:clamp(22px,4vw,30px);">Halo, <?= e($username) ?> 👋</h1>
                    <div class="mt-1" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <span class="badge <?= e($role_badge[1]) ?>"><?= e($role_badge[0]) ?></span>
                        <a class="back-link" href="profile.php" style="min-height:auto;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/>
                            </svg>
                            Lihat profil
                        </a>
                    </div>
                </div>
            </div>

            <?php flash_alert(); ?>

            <?php if (isset($_GET['akses'])): ?>
                <div class="alert alert-danger mt-2" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>
                    </svg>
                    <span>Kamu tidak punya akses ke halaman tersebut.</span>
                </div>
            <?php endif; ?>

            <div class="stats mt-3">
                <?php foreach ($stats as $s): ?>
                    <div class="stat">
                        <div class="stat-value"><?= e((string)$s['value']) ?></div>
                        <div class="stat-label"><?= e($s['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Menu Utama</h2>
                <a href="listings.php">Jelajahi katalog</a>
            </div>

            <div class="menu-grid">
                <a class="menu-item" href="listings.php">
                    <span class="mi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                    </span>
                    <div>
                        <div class="mi-title">Katalog</div>
                        <div class="mi-desc">Cari jasa &amp; produk</div>
                    </div>
                </a>

                <a class="menu-item" href="orders.php">
                    <span class="mi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 7h12l1 12H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/>
                        </svg>
                    </span>
                    <div>
                        <div class="mi-title">Pesanan Saya</div>
                        <div class="mi-desc">Status belanja &amp; bayar</div>
                    </div>
                </a>

                <a class="menu-item" href="favorites.php">
                    <span class="mi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20s-7-4.5-7-9.5A3.8 3.8 0 0 1 12 7a3.8 3.8 0 0 1 7 3.5C19 15.5 12 20 12 20z"/>
                        </svg>
                    </span>
                    <div>
                        <div class="mi-title">Favorit</div>
                        <div class="mi-desc">Listing tersimpan</div>
                    </div>
                </a>

                <a class="menu-item" href="profile.php">
                    <span class="mi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/>
                        </svg>
                    </span>
                    <div>
                        <div class="mi-title">Profil</div>
                        <div class="mi-desc">Data diri &amp; akun</div>
                    </div>
                </a>

                <?php if ($role === 'seller' || $role === 'admin'): ?>
                    <a class="menu-item" href="my-listings.php">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 7h16v13H4z"/><path d="M4 11h16M9 7V4h6v3"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Listing Saya</div>
                            <div class="mi-desc">Kelola jasa/produk</div>
                        </div>
                    </a>

                    <a class="menu-item" href="listing-form.php">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Tambah Listing</div>
                            <div class="mi-desc">Jual jasa/produk baru</div>
                        </div>
                    </a>

                    <a class="menu-item" href="seller-orders.php">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6h16M4 12h16M4 18h10"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Pesanan Masuk</div>
                            <div class="mi-desc">Order &amp; brief pembeli</div>
                        </div>
                    </a>
                <?php else: ?>
                    <a class="menu-item" href="become-seller.php">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Jadi Penjual</div>
                            <div class="mi-desc">Ajukan jadi seller</div>
                        </div>
                    </a>

                    <a class="menu-item" href="brief.php">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 4h10l4 4v12H5z"/><path d="M15 4v4h4M8 13h8M8 17h5"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Buat Brief Jasa</div>
                            <div class="mi-desc">Minta penawaran custom</div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <a class="menu-item" href="admin/index.php">
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title">Panel Admin</div>
                            <div class="mi-desc">Moderasi &amp; pengaturan</div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

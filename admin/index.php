<?php
$page_title  = 'Ringkasan Admin';
$admin_active = 'index';
include '_head.php';

function try_count(mysqli $db, string $sql): int
{
    try {
        $res = $db->query($sql);
        $row = $res ? $res->fetch_row() : null;
        return (int)($row[0] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

$stats = [
    ['Total Pengguna',     try_count($db, 'SELECT COUNT(*) FROM users'),            'users.php', 'Kelola pengguna'],
    ['Seller Disetujui',   try_count($db, "SELECT COUNT(*) FROM seller_profiles WHERE approval = 'approved'"), 'sellers.php', 'Approval seller'],
    ['Listing Tayang',     try_count($db, "SELECT COUNT(*) FROM listings WHERE moderation = 'approved'"), 'listings.php', 'Moderasi listing'],
    ['Pendapatan Selesai', rupiah((float)try_count($db, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'selesai'")), null, 'Pesanan selesai'],
];

$queues = [
    ['Persetujuan Seller',   try_count($db, "SELECT COUNT(*) FROM seller_profiles WHERE approval = 'pending'"), 'sellers.php', 'badge-pending'],
    ['Listing Menunggu Moderasi', try_count($db, "SELECT COUNT(*) FROM listings WHERE moderation = 'pending'"), 'listings.php', 'badge-pending'],
    ['Bukti Bayar Menunggu Verifikasi', try_count($db, "SELECT COUNT(*) FROM orders WHERE status = 'menunggu_bukti' AND payment_proof IS NOT NULL"), 'orders.php', 'badge-info'],
    ['Belum Bayar',           try_count($db, "SELECT COUNT(*) FROM orders WHERE status = 'menunggu_bukti' AND payment_proof IS NULL"), 'orders.php', 'badge-muted'],
    ['Brief Menunggu Penawaran', try_count($db, "SELECT COUNT(*) FROM briefs WHERE status = 'pending'"), null, 'badge-muted'],
    ['Laporan Belum Ditangani', try_count($db, "SELECT COUNT(*) FROM reports WHERE status = 'pending'"), 'reports.php', 'badge-sold'],
];
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Panel Admin</h1>
            <p>Kelola seller, moderasi listing, verifikasi pembayaran, dan pengguna.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <!-- Antrian kerja -->
            <div class="section-head"><h2>Perlu Tindakan</h2></div>
            <div class="menu-grid">
                <?php foreach ($queues as $q): ?>
                    <?php if ($q[2]): ?>
                        <a class="menu-item" href="<?= $q[2] ?>">
                    <?php else: ?>
                        <div class="menu-item" style="cursor:default;">
                    <?php endif; ?>
                        <span class="mi-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4M12 17v.01"/><path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0z"/>
                            </svg>
                        </span>
                        <div>
                            <div class="mi-title"><?= (int)$q[1] ?> · <?= e($q[0]) ?></div>
                            <div class="mi-desc"><?= $q[2] ? 'Buka antrean →' : 'Informasi' ?></div>
                        </div>
                    <?php if ($q[2]): ?></a><?php else: ?></div><?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Statistik -->
            <div class="section-head mt-4"><h2>Statistik</h2></div>
            <div class="stats">
                <?php foreach ($stats as $s): ?>
                    <div class="stat">
                        <div class="stat-value"><?= e((string)$s[1]) ?></div>
                        <div class="stat-label"><?= e($s[0]) ?></div>
                        <div class="mt-1">
                            <?php if ($s[2]): ?>
                                <a class="faint" href="<?= $s[2] ?>" style="color:var(--accent-strong);"><?= e($s[3]) ?> →</a>
                            <?php else: ?>
                                <span class="faint"><?= e($s[3]) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php include '_foot.php'; ?>

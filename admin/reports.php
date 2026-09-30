<?php
$page_title  = 'Laporan Listing';
$admin_active = 'reports';
include '_head.php';

// ── Aksi: tangani laporan ──
if (isset($_POST['aksi'])) {
    csrf_check();
    $rid = (int)($_POST['report_id'] ?? 0);
    $act = $_POST['act'] ?? '';
    if (in_array($act, ['resolve', 'reject'], true) && $rid > 0) {
        $status = $act === 'resolve' ? 'resolved' : 'rejected';
        $stmt = $db->prepare('UPDATE reports SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $rid);
        $stmt->execute();
        $stmt->close();
        set_flash($act === 'resolve' ? 'Laporan ditandai selesai ditangani.' : 'Laporan ditolak.', 'success');
    }
    header('Location: reports.php');
    exit;
}

$reports = [];
try {
    $reports = db_all(
        $db,
        'SELECT r.*, u.username AS reporter, l.title AS listing_title, l.moderation AS listing_moderation
         FROM reports r
         JOIN users u ON u.id = r.reporter_id
         JOIN listings l ON l.id = r.listing_id
         ORDER BY FIELD(r.status, "pending", "resolved", "rejected"), r.created_at DESC'
    );
} catch (Throwable $e) {}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Laporan Listing</h1>
            <p>Laporan dari pengguna tentang listing bermasalah.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if (!$reports): ?>
                <div class="empty">
                    <h3>Tidak ada laporan</h3>
                    <p>Bersih — belum ada laporan masuk.</p>
                </div>
            <?php else: ?>
                <div class="row-list">
                    <?php foreach ($reports as $r):
                        $badge = match ($r['status']) {
                            'resolved' => '<span class="badge badge-available">Selesai</span>',
                            'rejected' => '<span class="badge badge-muted">Ditolak</span>',
                            default    => '<span class="badge badge-pending">Menunggu</span>',
                        };
                    ?>
                        <div class="row-item">
                            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;">
                                <div style="flex:1;min-width:220px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <strong><?= e($r['listing_title']) ?></strong>
                                        <?= $badge ?>
                                        <span class="badge badge-muted">Moderasi: <?= e($r['listing_moderation']) ?></span>
                                    </div>
                                    <div class="faint mt-1">
                                        Dilaporkan oleh @<?= e($r['reporter']) ?> ·
                                        <?= e(date('d M Y H:i', strtotime($r['created_at']))) ?>
                                    </div>
                                    <p class="mt-1" style="font-size:14px;"><?= e($r['reason']) ?></p>
                                </div>

                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                    <a class="btn btn-ghost btn-sm" href="../listing-detail.php?id=<?= (int)$r['listing_id'] ?>" target="_blank">
                                        Lihat Listing
                                    </a>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="POST" data-confirm="Tandai laporan ini selesai ditangani?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="act" value="resolve">
                                            <button type="submit" name="aksi" class="btn btn-primary btn-sm">Tangani</button>
                                        </form>
                                        <form method="POST" data-confirm="Tolak laporan ini?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="act" value="reject">
                                            <button type="submit" name="aksi" class="btn btn-ghost btn-sm">Tolak</button>
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

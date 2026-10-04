<?php
$page_title  = 'Laporan Konten';
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
    if ($act === 'delete_review' && $rid > 0) {
        $row = db_one($db, 'SELECT review_id FROM reports WHERE id = ?', 'i', $rid);
        if ($row && !empty($row['review_id'])) {
            $stmt = $db->prepare('DELETE FROM reviews WHERE id = ?');
            $stmt->bind_param('i', $row['review_id']);
            $stmt->execute();
            $stmt->close();
            set_flash('Ulasan dihapus. Laporan terkait atas ulasan itu ikut terhapus.', 'success');
        } else {
            set_flash('Laporan ini bukan laporan ulasan.', 'danger');
        }
    }
    header('Location: reports.php');
    exit;
}

$reports = [];
try {
    $reports = db_all(
        $db,
        'SELECT r.*, u.username AS reporter,
                l.title AS listing_title, l.moderation AS listing_moderation,
                rv.rating AS review_rating, rv.comment AS review_comment,
                rv.listing_id AS review_listing_id, ub.username AS review_by
         FROM reports r
         JOIN users u ON u.id = r.reporter_id
         LEFT JOIN listings l ON l.id = r.listing_id
         LEFT JOIN reviews rv ON rv.id = r.review_id
         LEFT JOIN users ub ON ub.id = rv.buyer_id
         ORDER BY CASE r.status WHEN \'pending\' THEN 0 WHEN \'resolved\' THEN 1 WHEN \'rejected\' THEN 2 ELSE 3 END, r.created_at DESC'
    );
} catch (Throwable $e) {}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Laporan Konten</h1>
            <p>Laporan dari pengguna tentang listing maupun ulasan bermasalah.</p>
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
                                        <?php if ($r['review_id']): ?>
                                            <strong>Laporan Ulasan <?= str_repeat('★', max(1, min(5, (int)$r['review_rating']))) ?></strong>
                                        <?php else: ?>
                                            <strong><?= e($r['listing_title']) ?></strong>
                                        <?php endif; ?>
                                        <?= $badge ?>
                                        <?php if (!$r['review_id']): ?>
                                            <span class="badge badge-muted">Moderasi: <?= e($r['listing_moderation']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($r['review_id']): ?>
                                        <div class="faint mt-1">
                                            Ulasan oleh @<?= e($r['review_by'] ?? '—') ?><?= $r['listing_title'] ? ' · ' . e($r['listing_title']) : '' ?> ·
                                            Dilaporkan oleh @<?= e($r['reporter']) ?> ·
                                            <?= e(date('d M Y H:i', strtotime($r['created_at']))) ?>
                                        </div>
                                        <blockquote class="mt-1" style="font-size:14px;border-left:3px solid var(--border);padding-left:10px;margin:6px 0;">
                                            <?= $r['review_comment'] ? e($r['review_comment']) : '<em>(tanpa komentar)</em>' ?>
                                        </blockquote>
                                    <?php else: ?>
                                        <div class="faint mt-1">
                                            Dilaporkan oleh @<?= e($r['reporter']) ?> ·
                                            <?= e(date('d M Y H:i', strtotime($r['created_at']))) ?>
                                        </div>
                                    <?php endif; ?>
                                    <p class="mt-1" style="font-size:14px;"><?= e($r['reason']) ?></p>
                                </div>

                                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                    <?php if ($r['review_id']): ?>
                                        <?php if ($r['review_listing_id']): ?>
                                            <a class="btn btn-ghost btn-sm" href="../listing-detail.php?id=<?= (int)$r['review_listing_id'] ?>" target="_blank">
                                                Lihat Ulasan
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a class="btn btn-ghost btn-sm" href="../listing-detail.php?id=<?= (int)$r['listing_id'] ?>" target="_blank">
                                            Lihat Listing
                                        </a>
                                    <?php endif; ?>
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
                                        <?php if ($r['review_id']): ?>
                                            <form method="POST" data-confirm="Hapus ulasan ini? Laporan terkait ikut terhapus.">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                                                <input type="hidden" name="act" value="delete_review">
                                                <button type="submit" name="aksi" class="btn btn-danger btn-sm">Hapus Ulasan</button>
                                            </form>
                                        <?php endif; ?>
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

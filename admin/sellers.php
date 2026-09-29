<?php
$page_title  = 'Approval Seller';
$admin_active = 'sellers';
include '_head.php';

// ── Aksi: setujui / tolak / cabut seller ──
if (isset($_POST['aksi'])) {
    csrf_check();
    $pid = (int)($_POST['profile_id'] ?? 0);
    $act = $_POST['act'] ?? '';
    $p = db_one($db, 'SELECT * FROM seller_profiles WHERE id = ?', 'i', $pid);

    if (!$p) {
        set_flash('Pengajuan tidak ditemukan.', 'danger');
    } elseif (!in_array($act, ['approve', 'reject', 'revoke'], true)) {
        set_flash('Aksi tidak valid.', 'danger');
    } else {
        $approval = $act === 'approve' ? 'approved' : 'rejected';
        $stmt = $db->prepare('UPDATE seller_profiles SET approval = ? WHERE id = ?');
        $stmt->bind_param('si', $approval, $pid);
        $stmt->execute();
        $stmt->close();

        // Role user mengikuti status approval
        $role = $act === 'approve' ? 'seller' : 'buyer';
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ? AND role <> "admin"');
        $stmt->bind_param('si', $role, $p['user_id']);
        $stmt->execute();
        $stmt->close();

        set_flash(
            match ($act) {
                'approve' => 'Seller "' . $p['store_name'] . '" disetujui. Role user → seller.',
                'reject'  => 'Pengajuan "' . $p['store_name'] . '" ditolak.',
                'revoke'  => 'Akses seller "' . $p['store_name'] . '" dicabut.',
            },
            'success'
        );
    }
    header('Location: sellers.php');
    exit;
}

$profiles = [];
try {
    $profiles = db_all(
        $db,
        'SELECT sp.*, u.name, u.email, u.username, u.phone,
                (SELECT COUNT(*) FROM listings l WHERE l.seller_id = u.id) AS listing_count
         FROM seller_profiles sp JOIN users u ON u.id = sp.user_id
         ORDER BY FIELD(sp.approval, "pending", "approved", "rejected"), sp.updated_at DESC'
    );
} catch (Throwable $e) {}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Approval Seller</h1>
            <p>Setujui pengajuan toko agar teman-temanmu bisa mulai berjualan.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if (!$profiles): ?>
                <div class="empty">
                    <h3>Belum ada pengajuan seller</h3>
                    <p>Pengajuan dari calon penjual akan muncul di sini.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($profiles as $p):
                        $badge = match ($p['approval']) {
                            'approved' => '<span class="badge badge-available">Disetujui</span>',
                            'rejected' => '<span class="badge badge-sold">Ditolak</span>',
                            default    => '<span class="badge badge-pending">Menunggu</span>',
                        };
                    ?>
                        <div class="panel" style="padding:18px 20px;<?= $p['approval'] === 'pending' ? 'border-color:rgba(251,191,36,.35);' : '' ?>">
                            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;">
                                <span class="mi-icon" style="border-radius:50%;font-weight:700;">
                                    <?= strtoupper(substr($p['store_name'], 0, 1)) ?>
                                </span>

                                <div style="flex:1;min-width:220px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <strong style="font-size:15.5px;"><?= e($p['store_name']) ?></strong>
                                        <?= $badge ?>
                                        <span class="faint"><?= (int)$p['listing_count'] ?> listing</span>
                                    </div>
                                    <div class="faint" style="margin-top:3px;">
                                        <?= e($p['name'] ?: $p['username']) ?> · @<?= e($p['username']) ?> ·
                                        <?= e($p['email'] ?: '-') ?><?= $p['phone'] ? ' · ' . e($p['phone']) : '' ?>
                                    </div>
                                    <?php if ($p['store_desc']): ?>
                                        <p class="mt-1" style="font-size:13.5px;"><?= e($p['store_desc']) ?></p>
                                    <?php endif; ?>
                                    <div class="faint mt-1">Pencairan: <?= e($p['payout_info']) ?> ·
                                        diajukan <?= e(date('d M Y', strtotime($p['created_at']))) ?></div>
                                </div>

                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <?php if ($p['approval'] !== 'approved'): ?>
                                        <form method="POST" data-confirm="Setujui seller &quot;<?= e($p['store_name']) ?>&quot;?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="profile_id" value="<?= (int)$p['id'] ?>">
                                            <input type="hidden" name="act" value="approve">
                                            <button type="submit" name="aksi" class="btn btn-primary btn-sm">Setujui</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" data-confirm="Cabut akses seller &quot;<?= e($p['store_name']) ?>&quot;?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="profile_id" value="<?= (int)$p['id'] ?>">
                                            <input type="hidden" name="act" value="revoke">
                                            <button type="submit" name="aksi" class="btn btn-danger btn-sm">Cabut</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($p['approval'] === 'pending'): ?>
                                        <form method="POST" data-confirm="Tolak pengajuan &quot;<?= e($p['store_name']) ?>&quot;?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="profile_id" value="<?= (int)$p['id'] ?>">
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

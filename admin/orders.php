<?php
$page_title  = 'Verifikasi Bayar';
$admin_active = 'orders';
include '_head.php';

// ── Aksi verifikasi ──
if (isset($_POST['aksi'])) {
    csrf_check();
    $oid = (int)($_POST['order_id'] ?? 0);
    $act = $_POST['act'] ?? '';
    $o = db_one($db, 'SELECT * FROM orders WHERE id = ?', 'i', $oid);

    if (!$o) {
        set_flash('Pesanan tidak ditemukan.', 'danger');
    } elseif ($act === 'verify') {
        if (empty($o['payment_proof'])) {
            set_flash('Belum ada bukti pembayaran untuk diverifikasi.', 'danger');
        } else {
            $stmt = $db->prepare('UPDATE orders SET status = "diverifikasi" WHERE id = ?');
            $stmt->bind_param('i', $oid);
            $stmt->execute();
            $stmt->close();
            set_flash('Pembayaran pesanan ' . $o['order_code'] . ' diverifikasi.', 'success');
        }
    } elseif ($act === 'reject') {
        // Minta upload ulang: bukti dibuang, status kembali menunggu
        delete_upload($o['payment_proof'] ?? null);
        $stmt = $db->prepare('UPDATE orders SET payment_proof = NULL WHERE id = ?');
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();
        set_flash('Bukti ditolak — pembeli diminta upload ulang.', 'info');
    } elseif ($act === 'batal') {
        $stmt = $db->prepare('UPDATE orders SET status = "batal" WHERE id = ? AND status <> "selesai"');
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();
        set_flash('Pesanan dibatalkan.', 'info');
    } else {
        set_flash('Aksi tidak valid.', 'danger');
    }
    header('Location: orders.php');
    exit;
}

$orders = [];
try {
    $orders = db_all(
        $db,
        'SELECT o.*, ub.name AS buyer_name, ub.username AS buyer_username,
                COALESCE(sp.store_name, us.username) AS store_name
         FROM orders o
         JOIN users ub ON ub.id = o.buyer_id
         JOIN users us ON us.id = o.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = o.seller_id
         ORDER BY FIELD(o.status, "menunggu_bukti", "diverifikasi", "proses", "selesai", "batal"), o.created_at DESC'
    );
} catch (Throwable $e) {}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Verifikasi Bayar &amp; Pesanan</h1>
            <p>Periksa bukti transfer/QRIS yang diunggah pembeli.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if (!$orders): ?>
                <div class="empty">
                    <h3>Belum ada pesanan</h3>
                    <p>Pesanan dari pembeli akan muncul di sini.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($orders as $o):
                        $awaiting = $o['status'] === 'menunggu_bukti' && !empty($o['payment_proof']);
                        $no_proof = $o['status'] === 'menunggu_bukti' && empty($o['payment_proof']);
                    ?>
                        <div class="panel" style="padding:16px 18px;<?= $awaiting ? 'border-color:rgba(52,211,153,.4);' : '' ?>">
                            <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;">
                                <div style="flex:1;min-width:230px;">
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <strong><?= e($o['order_code']) ?></strong>
                                        <?= order_status_badge($o) ?>
                                        <?php if ($awaiting): ?><span class="badge badge-available">Perlu Verifikasi</span><?php endif; ?>
                                        <?php if ($no_proof): ?><span class="badge badge-muted">Belum Bayar</span><?php endif; ?>
                                    </div>
                                    <div class="card-title" style="margin-top:5px;"><?= e($o['title']) ?></div>
                                    <div class="faint">
                                        <?= e($o['buyer_name'] ?: $o['buyer_username']) ?> → <?= e($o['store_name']) ?> ·
                                        <?= e(date('d M Y H:i', strtotime($o['created_at']))) ?>
                                        <?php if ($o['payment_method']): ?> · <?= e($o['payment_method']) ?><?php endif; ?>
                                    </div>
                                </div>

                                <div style="text-align:right;display:flex;flex-direction:column;gap:10px;align-items:flex-end;">
                                    <div class="card-price"><?= rupiah($o['total']) ?></div>

                                    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
                                        <?php if ($o['payment_proof']): ?>
                                            <a class="btn btn-ghost btn-sm" href="../uploads/<?= e($o['payment_proof']) ?>" target="_blank">
                                                Lihat Bukti
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($awaiting): ?>
                                            <form method="POST" data-confirm="Verifikasi pembayaran <?= e($o['order_code']) ?>?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="act" value="verify">
                                                <button type="submit" name="aksi" class="btn btn-primary btn-sm">Verifikasi</button>
                                            </form>
                                            <form method="POST" data-confirm="Tolak bukti ini? Pembeli diminta upload ulang.">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="act" value="reject">
                                                <button type="submit" name="aksi" class="btn btn-danger btn-sm">Tolak Bukti</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!in_array($o['status'], ['selesai', 'batal'], true)): ?>
                                            <form method="POST" data-confirm="Batalkan pesanan ini?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <input type="hidden" name="act" value="batal">
                                                <button type="submit" name="aksi" class="btn btn-ghost btn-sm">Batalkan</button>
                                            </form>
                                        <?php endif; ?>
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

<?php include '_foot.php'; ?>

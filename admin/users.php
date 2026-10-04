<?php
$page_title  = 'Kelola Pengguna';
$admin_active = 'users';
include '_head.php';

$admin_id = (int)$_SESSION['user_id'];

// ── Aksi: ubah role / status user ──
if (isset($_POST['aksi'])) {
    csrf_check();
    $uid = (int)($_POST['user_id'] ?? 0);
    $act = $_POST['act'] ?? '';

    if ($uid === $admin_id) {
        set_flash('Kamu tidak bisa mengubah akunmu sendiri.', 'danger');
    } elseif ($uid <= 0) {
        set_flash('User tidak valid.', 'danger');
    } elseif (in_array($act, ['role_buyer', 'role_seller', 'role_admin'], true)) {
        $role = substr($act, 5);
        // Role seller hanya boleh untuk yang sudah punya profil approved?
        // Admin bebas — tapi ubah role seller hanya bila profil approved.
        if ($role === 'seller') {
            $p = db_one($db, 'SELECT approval FROM seller_profiles WHERE user_id = ?', 'i', $uid);
            if (!$p || $p['approval'] !== 'approved') {
                set_flash('User belum lolos approval seller. Setujui dulu di halaman Approval Seller.', 'danger');
                header('Location: users.php');
                exit;
            }
        }
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $role, $uid);
        $stmt->execute();
        $stmt->close();
        set_flash('Role user diperbarui → ' . $role . '.', 'success');
    } elseif ($act === 'toggle_status') {
        $u = db_one($db, 'SELECT status FROM users WHERE id = ?', 'i', $uid);
        if ($u) {
            $new = $u['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $db->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $new, $uid);
            $stmt->execute();
            $stmt->close();
            set_flash($new === 'active' ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.', 'success');
        }
    } else {
        set_flash('Aksi tidak valid.', 'danger');
    }
    header('Location: users.php');
    exit;
}

$q     = trim($_GET['q'] ?? '');
$users = [];
try {
    if ($q !== '') {
        $users = db_all(
            $db,
            'SELECT u.*, (SELECT COUNT(*) FROM listings l WHERE l.seller_id = u.id) AS listing_count,
                    (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.id) AS order_count
             FROM users u
             WHERE u.username LIKE ? OR u.name LIKE ? OR u.email LIKE ?
             ORDER BY CASE u.role WHEN \'admin\' THEN 0 WHEN \'seller\' THEN 1 WHEN \'buyer\' THEN 2 ELSE 3 END, u.created_at DESC',
            'sss', "%$q%", "%$q%", "%$q%"
        );
    } else {
        $users = db_all(
            $db,
            'SELECT u.*, (SELECT COUNT(*) FROM listings l WHERE l.seller_id = u.id) AS listing_count,
                    (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.id) AS order_count
             FROM users u
             ORDER BY CASE u.role WHEN \'admin\' THEN 0 WHEN \'seller\' THEN 1 WHEN \'buyer\' THEN 2 ELSE 3 END, u.created_at DESC'
        );
    }
} catch (Throwable $e) {}
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Kelola Pengguna</h1>
            <p><?= count($users) ?> pengguna terdaftar.</p>

            <form class="search-bar mt-2" action="users.php" method="get" role="search" style="margin-left:0;">
                <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama / username / email...">
                <button class="btn btn-primary" type="submit">Cari</button>
            </form>

            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if (!$users): ?>
                <div class="empty">
                    <h3>Pengguna tidak ditemukan</h3>
                    <p>Coba kata kunci lain.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aktivitas</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u):
                                $role_badge = match ($u['role']) {
                                    'admin'  => '<span class="badge badge-info">Admin</span>',
                                    'seller' => '<span class="badge badge-available">Seller</span>',
                                    default  => '<span class="badge badge-muted">Buyer</span>',
                                };
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($u['name'] ?: $u['username']) ?></strong>
                                        <div class="faint">@<?= e($u['username']) ?> · <?= e($u['email'] ?: 'tanpa email') ?></div>
                                    </td>
                                    <td><?= $role_badge ?></td>
                                    <td>
                                        <?= $u['status'] === 'active'
                                            ? '<span class="badge badge-available">Aktif</span>'
                                            : '<span class="badge badge-sold">Nonaktif</span>' ?>
                                    </td>
                                    <td class="faint"><?= (int)$u['listing_count'] ?> listing · <?= (int)$u['order_count'] ?> pesanan<br>
                                        daftar <?= e(date('d M Y', strtotime($u['created_at'] ?? 'now'))) ?></td>
                                    <td style="text-align:right;white-space:nowrap;">
                                        <?php if ((int)$u['id'] !== $admin_id): ?>
                                            <form method="POST" action="users.php" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="act" value="toggle_status">
                                                <button type="submit" name="aksi" class="btn btn-ghost btn-sm">
                                                    <?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>
                                            <?php if ($u['role'] !== 'buyer'): ?>
                                                <form method="POST" action="users.php" style="display:inline;"
                                                      data-confirm="Jadikan <?= e($u['username']) ?> buyer?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="act" value="role_buyer">
                                                    <button type="submit" name="aksi" class="btn btn-soft btn-sm">→ Buyer</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($u['role'] !== 'seller'): ?>
                                                <form method="POST" action="users.php" style="display:inline;"
                                                      data-confirm="Jadikan <?= e($u['username']) ?> seller?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="act" value="role_seller">
                                                    <button type="submit" name="aksi" class="btn btn-soft btn-sm">→ Seller</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($u['role'] !== 'admin'): ?>
                                                <form method="POST" action="users.php" style="display:inline;"
                                                      data-confirm="Jadikan <?= e($u['username']) ?> ADMIN? Yakin?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="act" value="role_admin">
                                                    <button type="submit" name="aksi" class="btn btn-soft btn-sm">→ Admin</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="faint">(akunmu)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '_foot.php'; ?>

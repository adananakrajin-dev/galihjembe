<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

// ── Aksi: buat brief baru ──
$error = '';
if (isset($_POST['buat_brief'])) {
    csrf_check();

    $title      = trim($_POST['title'] ?? '');
    $reqs       = trim($_POST['requirements'] ?? '');
    $seller_id  = (int)($_POST['seller_id'] ?? 0);
    $budget_min = $_POST['budget_min'] !== '' ? (float)str_replace([',', '.'], '', $_POST['budget_min']) : null;
    $budget_max = $_POST['budget_max'] !== '' ? (float)str_replace([',', '.'], '', $_POST['budget_max']) : null;
    $deadline   = $_POST['deadline'] !== '' ? $_POST['deadline'] : null;

    if (strlen($title) < 5 || strlen($title) > 150) {
        $error = 'Judul brief 5–150 karakter.';
    } elseif (strlen($reqs) < 20) {
        $error = 'Detail kebutuhan minimal 20 karakter — biar penjual paham maksudmu.';
    } elseif ($budget_min !== null && $budget_max !== null && $budget_min > $budget_max) {
        $error = 'Budget minimum tidak boleh melebihi budget maksimum.';
    } elseif ($deadline !== null && !strtotime($deadline)) {
        $error = 'Format deadline tidak valid.';
    } elseif ($seller_id > 0 && !db_one($db, 'SELECT id FROM users WHERE id = ?', 'i', $seller_id)) {
        $error = 'Penjual tidak ditemukan.';
    } else {
        // Brief terbuka = seller_id NULL (FK), bukan 0
        $seller_bind = $seller_id > 0 ? $seller_id : null;

        $stmt = $db->prepare(
            'INSERT INTO briefs (buyer_id, seller_id, title, requirements, budget_min, budget_max, deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iissdds', $user_id, $seller_bind, $title, $reqs, $budget_min, $budget_max, $deadline);
        if ($stmt->execute()) {
            $stmt->close();
            set_flash('Brief terkirim! Penjual akan memberi penawaran harga.', 'success');
            header('Location: brief.php');
            exit;
        }
        $stmt->close();
        $error = 'Gagal mengirim brief, coba lagi.';
    }
}

// ── Aksi: tolak penawaran ──
if (isset($_POST['tolak'])) {
    csrf_check();
    $bid = (int)($_POST['brief_id'] ?? 0);
    $stmt = $db->prepare('UPDATE briefs SET status = "rejected" WHERE id = ? AND buyer_id = ? AND status = "quoted"');
    $stmt->bind_param('ii', $bid, $user_id);
    $stmt->execute();
    $stmt->close();
    set_flash('Penawaran ditolak.', 'info');
    header('Location: brief.php');
    exit;
}

// ── Penjual dituju dari link listing detail ──
$target_seller = (int)($_GET['seller'] ?? 0);
$target_name   = '';
if ($target_seller > 0) {
    try {
        $t = db_one(
            $db,
            'SELECT COALESCE(sp.store_name, u.username) AS name
             FROM users u LEFT JOIN seller_profiles sp ON sp.user_id = u.id
             WHERE u.id = ? AND (sp.approval = "approved" OR sp.user_id IS NULL)',
            'i', $target_seller
        );
        $target_name = $t['name'] ?? '';
        if ($target_name === '') { $target_seller = 0; }
    } catch (Throwable $e) { $target_seller = 0; }
}

// ── Daftar brief saya ──
$briefs = [];
try {
    $briefs = db_all(
        $db,
        'SELECT b.*, COALESCE(sp.store_name, u.username) AS store_name
         FROM briefs b
         LEFT JOIN users u ON u.id = b.seller_id
         LEFT JOIN seller_profiles sp ON sp.user_id = b.seller_id
         WHERE b.buyer_id = ?
         ORDER BY b.created_at DESC',
        'i', $user_id
    );
} catch (Throwable $e) {
    $briefs = [];
}

function brief_badge(string $s): string
{
    return match ($s) {
        'pending'  => '<span class="badge badge-pending">Menunggu Penawaran</span>',
        'quoted'   => '<span class="badge badge-info">Ada Penawaran</span>',
        'accepted' => '<span class="badge badge-available">Dipesan</span>',
        'rejected' => '<span class="badge badge-muted">Ditolak</span>',
        default    => '<span class="badge badge-muted">Ditutup</span>',
    };
}

$page_title = 'Brief Jasa';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Brief Jasa Custom</h1>
            <p>Butuh website yang belum ada di katalog? Tulis kebutuhanmu, penjual akan kasih penawaran harga.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <div class="grid-2" style="align-items:start;">

                <!-- Form buat brief -->
                <div>
                    <h3 class="mb-2">Buat Brief Baru</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-2" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="brief.php" method="POST" class="form">
                        <?= csrf_field() ?>
                        <?php if ($target_seller > 0): ?>
                            <input type="hidden" name="seller_id" value="<?= $target_seller ?>">
                            <div class="alert alert-info">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                                <span>Brief ditujukan ke <strong><?= e($target_name) ?></strong>.</span>
                            </div>
                        <?php else: ?>
                            <div class="field">
                                <label for="seller_id">Penjual Tujuan <span class="faint">(opsional)</span></label>
                                <select id="seller_id" name="seller_id">
                                    <option value="0">Terbuka untuk semua penjual</option>
                                    <?php if ($target_seller > 0): ?>
                                        <option value="<?= $target_seller ?>" selected><?= e($target_name) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="field">
                            <label for="title">Judul Kebutuhan</label>
                            <input id="title" type="text" name="title" required minlength="5" maxlength="150"
                                   placeholder="Contoh: Website Katalog Untuk Toko Roti" value="<?= e($_POST['title'] ?? '') ?>">
                        </div>

                        <div class="field">
                            <label for="requirements">Detail Kebutuhan</label>
                            <textarea id="requirements" name="requirements" required minlength="20" style="min-height:130px;"
                                      placeholder="Ceritakan: tujuan website, fitur yang dibutuhkan (katalog, form pemesanan, blog...), referensi desain, target waktu..."><?= e($_POST['requirements'] ?? '') ?></textarea>
                            <span class="hint">Minimal 20 karakter. Makin detail, makin akurat penawarannya.</span>
                        </div>

                        <div class="form-row">
                            <div class="field">
                                <label for="budget_min">Budget Minimum (Rp) <span class="faint">(opsional)</span></label>
                                <input id="budget_min" type="text" name="budget_min" inputmode="numeric"
                                       placeholder="1000000" value="<?= e($_POST['budget_min'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label for="budget_max">Budget Maksimum (Rp) <span class="faint">(opsional)</span></label>
                                <input id="budget_max" type="text" name="budget_max" inputmode="numeric"
                                       placeholder="5000000" value="<?= e($_POST['budget_max'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="deadline">Target Selesai <span class="faint">(opsional)</span></label>
                            <input id="deadline" type="date" name="deadline" value="<?= e($_POST['deadline'] ?? '') ?>">
                        </div>

                        <button type="submit" name="buat_brief" class="btn btn-primary btn-block">Kirim Brief</button>
                    </form>
                </div>

                <!-- Daftar brief saya -->
                <div>
                    <div>
                        <h3 class="mb-2">Brief Saya (<?= count($briefs) ?>)</h3>

                        <?php if (!$briefs): ?>
                            <p class="faint">Belum ada brief. Isi formulir di samping untuk mulai.</p>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <?php foreach ($briefs as $b): ?>
                                    <div class="order-summary">
                                        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
                                            <strong style="font-size:15px;"><?= e($b['title']) ?></strong>
                                            <?= brief_badge($b['status']) ?>
                                        </div>
                                        <div class="faint" style="margin-top:4px;">
                                            <?= e(date('d M Y', strtotime($b['created_at']))) ?>
                                            <?php if ($b['store_name']): ?> · <?= e($b['store_name']) ?><?php endif; ?>
                                            <?php if ($b['deadline']): ?> · target <?= e(date('d M Y', strtotime($b['deadline']))) ?><?php endif; ?>
                                        </div>
                                        <p class="mt-1" style="font-size:13.5px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?= e($b['requirements']) ?>
                                        </p>

                                        <?php if ($b['status'] === 'quoted'): ?>
                                            <div class="mt-2" style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;background:var(--accent-soft);border:1px dashed rgba(99,102,241,.4);border-radius:10px;padding:10px 14px;">
                                                <div>
                                                    <div class="faint">Penawaran <?= e($b['store_name']) ?></div>
                                                    <div class="card-price"><?= rupiah($b['quote_price']) ?></div>
                                                </div>
                                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                                    <a class="btn btn-primary btn-sm" href="order-create.php?brief=<?= (int)$b['id'] ?>">Terima &amp; Pesan</a>
                                                    <form method="POST" action="brief.php" data-confirm="Tolak penawaran ini?">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="brief_id" value="<?= (int)$b['id'] ?>">
                                                        <button type="submit" name="tolak" class="btn btn-ghost btn-sm">Tolak</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php elseif ($b['status'] === 'accepted'): ?>
                                            <div class="mt-2">
                                                <a class="btn btn-soft btn-sm" href="orders.php">Lihat Pesanan</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

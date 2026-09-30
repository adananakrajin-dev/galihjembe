<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

// Data user
$user = null;
try { $user = db_one($db, 'SELECT * FROM users WHERE id = ?', 'i', $user_id); }
catch (Throwable $e) {}
if (!$user) {
    header('Location: logout.php');
    exit;
}

$profile = null;
try { $profile = db_one($db, 'SELECT * FROM seller_profiles WHERE user_id = ?', 'i', $user_id); }
catch (Throwable $e) {}

// ── Aksi: ubah data diri ──
$error = '';
if (isset($_POST['simpan_profil'])) {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($name === '') {
        $error = 'Nama wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $dup = db_one($db, 'SELECT id FROM users WHERE email = ? AND id <> ?', 'si', $email, $user_id);
        if ($dup) {
            $error = 'Email sudah dipakai akun lain.';
        } else {
            $stmt = $db->prepare('UPDATE users SET name=?, email=?, phone=?, location=? WHERE id=?');
            $stmt->bind_param('ssssi', $name, $email, $phone, $location, $user_id);
            if ($stmt->execute()) {
                $stmt->close();
                set_flash('Profil berhasil diperbarui.', 'success');
                header('Location: profile.php');
                exit;
            }
            $stmt->close();
            $error = 'Gagal menyimpan profil.';
        }
    }
}

// ── Aksi: ganti password ──
if (isset($_POST['ganti_password'])) {
    csrf_check();

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        $error = 'Password saat ini salah.';
    } elseif (strlen($new) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password=? WHERE id=?');
        $stmt->bind_param('si', $hash, $user_id);
        if ($stmt->execute()) {
            $stmt->close();
            session_regenerate_id(true);
            set_flash('Password berhasil diganti.', 'success');
            header('Location: profile.php');
            exit;
        }
        $stmt->close();
        $error = 'Gagal mengganti password.';
    }
}

$initial  = strtoupper(substr($user['name'] ?: $user['username'], 0, 1));
$role_lbl = ['admin' => ['Admin', 'badge-info'], 'seller' => ['Penjual', 'badge-available']][current_role()] ?? ['Pembeli', 'badge-muted'];

$page_title = 'Profil';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <h1>Profil Saya</h1>
            <p>Perbarui data diri dan keamanan akunmu.</p>
            <?php flash_alert(); ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger mb-3" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid-2" style="align-items:start;">

                <!-- Ringkasan akun -->
                <div>
                    <div>
                        <div class="feature" style="align-items:center;">
                            <span class="mi-icon" style="width:64px;height:64px;border-radius:50%;font-size:24px;font-weight:700;">
                                <?= e($initial) ?>
                            </span>
                            <div>
                                <div class="mi-title" style="font-size:18px;"><?= e($user['name'] ?: $user['username']) ?></div>
                                <div class="mi-desc">@<?= e($user['username']) ?> · sejak <?= e(date('M Y', strtotime($user['created_at'] ?? 'now'))) ?></div>
                                <div class="mt-1"><span class="badge <?= e($role_lbl[1]) ?>"><?= e($role_lbl[0]) ?></span></div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="link-list">
                            <?php if (current_role() === 'admin'): ?>
                                <a class="link-item" href="admin/index.php">
                                    Panel Admin
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                                </a>
                            <?php elseif (current_role() === 'seller'): ?>
                                <a class="link-item" href="my-listings.php">
                                    Kelola Toko &amp; Listing
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                                </a>
                            <?php else: ?>
                                <a class="link-item" href="become-seller.php">
                                    <?= $profile ? 'Status Pengajuan Seller' : 'Jadi Penjual' ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                                </a>
                            <?php endif; ?>

                            <a class="link-item" href="orders.php">
                                Pesanan Saya
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                            </a>
                            <a class="link-item" href="favorites.php">
                                Favorit Saya
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                            </a>
                        </div>
                    </div>

                    <?php if ($profile): ?>
                        <div class="divider"></div>

                        <div>
                            <div class="feature">
                                <span class="mi-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v13H4z"/><path d="M4 11h16M9 7V4h6v3"/></svg>
                                </span>
                                <div>
                                    <div class="mi-title"><?= e($profile['store_name']) ?></div>
                                    <div class="mi-desc">
                                        Status toko:
                                        <strong style="color:<?= $profile['approval'] === 'approved' ? 'var(--success)' : 'var(--warning)' ?>">
                                            <?= $profile['approval'] === 'approved' ? 'Disetujui' : 'Menunggu Approval Admin' ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form data diri + password -->
                <div>
                    <div>
                        <h3 class="mb-2">Data Diri</h3>
                        <form action="profile.php" method="POST" class="form">
                            <?= csrf_field() ?>

                            <div class="field">
                                <label for="name">Nama Lengkap</label>
                                <input id="name" type="text" name="name" required value="<?= e($_POST['name'] ?? $user['name']) ?>">
                            </div>
                            <div class="field">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" required value="<?= e($_POST['email'] ?? $user['email']) ?>">
                            </div>
                            <div class="field">
                                <label for="phone">Nomor WhatsApp</label>
                                <input id="phone" type="tel" name="phone" placeholder="08xxxxxxxxxx"
                                       value="<?= e($_POST['phone'] ?? $user['phone']) ?>">
                                <span class="hint">Dipakai pembeli menghubungimu via WA. Wajib untuk seller.</span>
                            </div>
                            <div class="field">
                                <label for="location">Lokasi</label>
                                <input id="location" type="text" name="location" placeholder="Jakarta Selatan"
                                       value="<?= e($_POST['location'] ?? $user['location']) ?>">
                            </div>

                            <button type="submit" name="simpan_profil" class="btn btn-primary btn-block">Simpan Perubahan</button>
                        </form>
                    </div>

                    <div class="divider"></div>

                    <div>
                        <h3 class="mb-2">Ganti Password</h3>
                        <form action="profile.php" method="POST" class="form">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="current_password">Password Saat Ini</label>
                                <input id="current_password" type="password" name="current_password" required
                                       autocomplete="current-password">
                            </div>
                            <div class="form-row">
                                <div class="field">
                                    <label for="new_password">Password Baru</label>
                                    <input id="new_password" type="password" name="new_password" required minlength="8"
                                           autocomplete="new-password" placeholder="Min. 8 karakter">
                                </div>
                                <div class="field">
                                    <label for="confirm_password">Ulangi Password Baru</label>
                                    <input id="confirm_password" type="password" name="confirm_password" required
                                           autocomplete="new-password">
                                </div>
                            </div>
                            <button type="submit" name="ganti_password" class="btn btn-soft btn-block">Ganti Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$user_id = (int)$_SESSION['user_id'];

// Sudah seller / admin? → ke dashboard
if (in_array(current_role(), ['seller', 'admin'], true)) {
    set_flash('Kamu sudah menjadi penjual.', 'info');
    header('Location: my-listings.php');
    exit;
}

// Sudah pernah mengajukan?
$profile = null;
try { $profile = db_one($db, 'SELECT * FROM seller_profiles WHERE user_id = ?', 'i', $user_id); }
catch (Throwable $e) {}

// Data akun utk prefill telepon & email
$me = ['phone' => '', 'email' => ''];
try { $me = (db_one($db, 'SELECT phone, email FROM users WHERE id = ?', 'i', $user_id) ?? []) + $me; }
catch (Throwable $e) {}

if ($profile && $profile['approval'] === 'approved') {
    set_flash('Akunmu sudah disetujui admin. Silakan login ulang untuk memperbarui sesi.', 'info');
    header('Location: login.php');
    exit;
}

// ── Proses pengajuan ──
$error = '';
if (isset($_POST['ajukan'])) {
    csrf_check();

    if ($profile) {
        $error = 'Pengajuanmu sudah dikirim sebelumnya — menunggu persetujuan admin.';
    } else {
        $store_name  = trim($_POST['store_name'] ?? '');
        $store_desc  = trim($_POST['store_desc'] ?? '');
        $payout_info = trim($_POST['payout_info'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $home_addr   = trim($_POST['home_address'] ?? '');

        // Data verifikasi wajib (anti-penipuan): telepon aktif, email aktif, alamat rumah
        $phone_norm = str_replace([' ', '-', '(', ')'], '', $phone);
        if (strlen($store_name) < 3 || strlen($store_name) > 100) {
            $error = 'Nama toko 3–100 karakter.';
        } elseif (strlen($store_desc) > 500) {
            $error = 'Deskripsi toko maks 500 karakter.';
        } elseif ($payout_info === '') {
            $error = 'Isi rekening/e-wallet untuk pencairan dana.';
        } elseif (!preg_match('/^(\+62|62|0)8[1-9][0-9]{6,12}$/', $phone_norm)) {
            $error = 'Nomor telepon tidak valid. Gunakan format aktif, contoh: 081234567890.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid. Contoh: nama@domain.com';
        } elseif (strlen($home_addr) < 10 || strlen($home_addr) > 255) {
            $error = 'Alamat rumah 10–255 karakter (wajib untuk verifikasi admin).';
        } else {
            // Email harus aktif & belum dipakai akun lain
            try {
                $dupe = db_one(
                    $db,
                    'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1',
                    'si', $email, $user_id
                );
            } catch (Throwable $e) { $dupe = false; }

            if ($dupe) {
                $error = 'Email sudah terdaftar di akun lain. Gunakan email aktif milikmu sendiri.';
            } else {
                // Simpan ke profil akun (telepon & email dipakai admin/WA)
                $upd = $db->prepare('UPDATE users SET phone = ?, email = ? WHERE id = ?');
                $upd->bind_param('ssi', $phone_norm, $email, $user_id);
                $upd->execute();
                $upd->close();

                $stmt = $db->prepare(
                    'INSERT INTO seller_profiles (user_id, store_name, store_desc, payout_info, home_address, approval)
                     VALUES (?, ?, ?, ?, ?, \'pending\')'
                );
                $stmt->bind_param('issss', $user_id, $store_name, $store_desc, $payout_info, $home_addr);
                if ($stmt->execute()) {
                    $stmt->close();
                    set_flash('Pengajuan seller terkirim! Menunggu persetujuan admin.', 'success');
                    header('Location: dashboard.php');
                    exit;
                }
                $stmt->close();
                $error = 'Gagal mengirim pengajuan, coba lagi.';
            }
        }
    }
}

$page_title = 'Jadi Penjual';
include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <a class="back-link" href="dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                Kembali ke dashboard
            </a>
            <h1>Jadi Penjual di SESSIONS</h1>
            <p>Jual jasa web atau produkmu sendiri. Ajukan diri, tunggu approval admin, lalu mulai listing.</p>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <div class="grid-2" style="align-items:start;">
                <!-- Cara kerja -->
                <div class="panel">
                    <h3 class="mb-2">Bagaimana Caranya?</h3>
                    <div style="display:flex;flex-direction:column;gap:16px;">
                        <div class="feature">
                            <span class="mi-icon" style="border-radius:50%;font-weight:700;">1</span>
                            <div>
                                <div class="mi-title">Isi Profil Toko</div>
                                <div class="mi-desc">Nama toko, deskripsi singkat, info pencairan, plus telepon, email, dan alamat rumah untuk verifikasi admin.</div>
                            </div>
                        </div>
                        <div class="feature">
                            <span class="mi-icon" style="border-radius:50%;font-weight:700;">2</span>
                            <div>
                                <div class="mi-title">Menunggu Approval Admin</div>
                                <div class="mi-desc">Admin meninjau pengajuanmu — biasanya 1×24 jam pada jam kerja.</div>
                            </div>
                        </div>
                        <div class="feature">
                            <span class="mi-icon" style="border-radius:50%;font-weight:700;">3</span>
                            <div>
                                <div class="mi-title">Mulai Menjual</div>
                                <div class="mi-desc">Setelah disetujui, kamu bisa membuat listing, menerima pesanan, dan brief custom.</div>
                            </div>
                        </div>
                    </div>

                    <?php if ($profile): ?>
                        <div class="divider"></div>
                        <div class="alert alert-info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                            <span>Pengajuan untuk <strong>"<?= e($profile['store_name']) ?>"</strong> sudah dikirim — status:
                                <strong><?= e($profile['approval']) ?></strong>. Menunggu persetujuan admin.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form -->
                <div class="panel">
                    <h3 class="mb-2">Formulir Pengajuan Seller</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-2" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                            <span><?= e($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($profile): ?>
                        <div class="alert alert-info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                            <span>Pengajuan sudah dikirim dan tidak bisa diubah. Admin akan meninjau secepatnya.</span>
                        </div>
                    <?php else: ?>
                        <form action="become-seller.php" method="POST" class="form">
                            <?= csrf_field() ?>

                            <div class="field">
                                <label for="store_name">Nama Toko</label>
                                <input id="store_name" type="text" name="store_name" required minlength="3" maxlength="100"
                                       placeholder="Contoh: Dcode Studio" value="<?= e($_POST['store_name'] ?? '') ?>">
                            </div>

                            <div class="field">
                                <label for="store_desc">Deskripsi Toko <span class="faint">(opsional)</span></label>
                                <textarea id="store_desc" name="store_desc" maxlength="500" style="min-height:90px;"
                                          placeholder="Ceritakan singkat jasa/produk yang kamu jual..."><?= e($_POST['store_desc'] ?? '') ?></textarea>
                            </div>

                            <div class="field">
                                <label for="payout_info">Rekening / E-Wallet Pencairan</label>
                                <input id="payout_info" type="text" name="payout_info" required
                                       placeholder="BCA 1234567890 a.n. ..." value="<?= e($_POST['payout_info'] ?? '') ?>">
                                <span class="hint">Dipakai admin saat mencairkan dana penjualan yang sudah selesai.</span>
                            </div>

                            <div class="divider"></div>

                            <div class="field">
                                <label for="phone">Nomor Telepon Aktif</label>
                                <input id="phone" type="tel" name="phone" required autocomplete="tel" inputmode="tel"
                                       placeholder="081234567890"
                                       value="<?= e($_POST['phone'] ?? ($me['phone'] ?? '')) ?>">
                                <span class="hint">Wajib aktif &amp; bisa dihubungi — dipakai admin untuk verifikasi akun.</span>
                            </div>

                            <div class="field">
                                <label for="email">Email Aktif</label>
                                <input id="email" type="email" name="email" required autocomplete="email"
                                       placeholder="nama@domain.com"
                                       value="<?= e($_POST['email'] ?? ($me['email'] ?? '')) ?>">
                                <span class="hint">Wajib email aktif milikmu sendiri; tidak boleh dipakai akun lain.</span>
                            </div>

                            <div class="field">
                                <label for="home_address">Alamat Rumah</label>
                                <textarea id="home_address" name="home_address" required minlength="10" maxlength="255"
                                          style="min-height:80px;"
                                          placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kota"><?= e($_POST['home_address'] ?? '') ?></textarea>
                                <span class="hint">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;vertical-align:-2px;">
                                        <rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                    </svg>
                                    Untuk verifikasi anti-penipuan — <strong>hanya admin</strong> yang dapat melihatnya.
                                </span>
                            </div>

                            <button type="submit" name="ajukan" class="btn btn-primary btn-block">Kirim Pengajuan</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

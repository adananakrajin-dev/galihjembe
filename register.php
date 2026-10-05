<?php
include 'database.php';
include 'includes/functions.php';

if (is_logged()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if (isset($_POST['submit_register'])) {
    csrf_check();

    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ── Validasi (PRD §22) ──
    if ($name === '') {
        $error = 'Nama wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid. Contoh: user@example.com';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = 'Username 3–20 karakter, hanya huruf, angka, dan garis bawah.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok.';
    } else {
        // Cek duplikat email & username (1 email = 1 akun)
        $dup = db_one(
            $db,
            'SELECT id,
                    CASE WHEN email = ? THEN \'email\' ELSE \'username\' END AS jenis
             FROM users WHERE email = ? OR username = ? LIMIT 1',
            'sss', $email, $email, $username
        );

        if ($dup) {
            $error = $dup['jenis'] === 'email'
                ? 'Email sudah terdaftar. Gunakan email lain atau masuk.'
                : 'Username sudah dipakai. Gunakan username lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                'INSERT INTO users (name, email, username, phone, password, role, status)
                 VALUES (?, ?, ?, ?, ?, \'buyer\', \'active\')'
            );
            $stmt->bind_param('sssss', $name, $email, $username, $phone, $hash);

            if ($stmt->execute()) {
                set_flash('Registrasi berhasil! Silakan masuk dengan akunmu.', 'success');
                header('Location: login.php');
                exit;
            }
            $stmt->close();
            $error = 'Gagal mendaftar, coba lagi nanti.';
        }
    }
}

$page_title = 'Daftar';
$active = '';
include 'includes/header.php';
?>

<main class="auth-wrap">
    <div class="auth-card">
        <div class="panel">
            <div class="auth-head">
                <div class="logo-mark">S</div>
                <h2>Buat Akun Baru</h2>
                <p>Gratis — jadi pembeli dulu, bisa ajukan jadi seller kapan saja.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-2" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>
                    </svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="form">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="name">Nama Lengkap</label>
                    <input id="name" type="text" name="name" required autofocus
                           autocomplete="name" placeholder="Nama kamu" value="<?= e($_POST['name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required
                           autocomplete="email" placeholder="user@example.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" required
                           autocomplete="username" placeholder="3–20 karakter" value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="phone">Nomor WhatsApp <span class="faint">(opsional)</span></label>
                    <input id="phone" type="tel" name="phone" autocomplete="tel"
                           placeholder="08xxxxxxxxxx" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" required
                               autocomplete="new-password" placeholder="Min. 8 karakter">
                    </div>
                    <div class="field">
                        <label for="confirm_password">Ulangi Password</label>
                        <input id="confirm_password" type="password" name="confirm_password" required
                               autocomplete="new-password" placeholder="Ketik ulang">
                    </div>
                </div>

                <button type="submit" name="submit_register" class="btn btn-primary btn-block">Daftar Sekarang</button>
            </form>

            <div class="auth-foot">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

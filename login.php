<?php
include 'database.php';
include 'includes/functions.php';

if (is_logged()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if (isset($_POST['submit_login'])) {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Throttle: maksimal 5 percobaan gagal per 5 menit (per sesi)
    $now = time();
    $log = array_values(array_filter(
        $_SESSION['login_attempts'] ?? [],
        static fn($t): bool => $now - (int)$t < 300
    ));

    if (count($log) >= 5) {
        $error = 'Terlalu banyak percobaan login yang gagal. Tunggu beberapa menit lalu coba lagi.';
    } else {
    $row = db_one($db, 'SELECT * FROM users WHERE username = ?', 's', $username);

    if ($row && ($row['status'] ?? 'active') !== 'active') {
        $error = 'Akun ini dinonaktifkan. Hubungi admin untuk mengaktifkan kembali.';
    } elseif ($row) {
        $stored = $row['password'];
        $valid  = password_verify($password, $stored);

        // Kompatibilitas data lama: password masih plaintext → cocokkan langsung,
        // lalu otomatis upgrade ke hash saat login pertama.
        $is_known_hash = !empty(password_get_info($stored)['algo']);
        if (!$valid && !$is_known_hash && hash_equals($stored, $password)) {
            $valid = true;
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $new_hash, $row['id']);
            $stmt->execute();
            $stmt->close();
        }

        if ($valid) {
            unset($_SESSION['login_attempts']);
            session_regenerate_id(true);
            $_SESSION['sudah_login'] = true;
            $_SESSION['user_id']     = (int)$row['id'];
            $_SESSION['username']    = $row['username'];
            $_SESSION['role']        = $row['role'] ?? 'buyer';

            header('Location: dashboard.php');
            exit;
        }
        $error = 'Username atau password salah!';
    } else {
        $error = 'Username atau password salah!';
    }

    if ($error !== '') {
        $log[] = $now;
        $_SESSION['login_attempts'] = $log;
    }
    }
}

$page_title = 'Masuk';
$active = '';
include 'includes/header.php';
?>

<main class="auth-wrap">
    <div class="auth-card">
        <div class="panel">
            <div class="auth-head">
                <div class="logo-mark">S</div>
                <h2>Selamat datang kembali</h2>
                <p>Masuk untuk mengelola listing, brief, dan pesananmu.</p>
            </div>

            <?php if (isset($_GET['out'])): ?>
                <div class="alert alert-info mb-2" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>
                    </svg>
                    <span>Kamu sudah keluar dari akun. Silakan masuk kembali bila perlu.</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-2" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>
                    </svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="form">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" required autofocus
                           autocomplete="username" placeholder="Masukkan username"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required
                           autocomplete="current-password" placeholder="Masukkan password">
                </div>

                <button type="submit" name="submit_login" class="btn btn-primary btn-block">Masuk</button>
            </form>

            <div class="auth-foot">
                Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

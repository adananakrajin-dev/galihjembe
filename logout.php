<?php
include 'includes/functions.php';

// Proses logout (hanya POST + token CSRF)
if (isset($_POST['logout'])) {
    csrf_check();
    session_unset();
    session_destroy();
    header('Location: login.php?out=1');
    exit;
}

// Halaman konfirmasi — kalau tidak sedang login, langsung ke login
if (!is_logged()) {
    header('Location: login.php');
    exit;
}

$initial = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
$username = $_SESSION['username'] ?? 'Pengguna';

$page_title = 'Keluar';
include 'includes/header.php';
?>

<main class="auth-wrap">
    <div class="auth-card">
        <div class="panel center">
            <div class="logo-mark" style="width:64px;height:64px;border-radius:50%;font-size:24px;margin-bottom:16px;">
                <?= e($initial) ?>
            </div>

            <h2>Keluar dari akun?</h2>
            <p class="mt-1">Kamu akan keluar sebagai <strong style="color:var(--text)"><?= e($username) ?></strong>.
                Sesi akan dihapus dari perangkat ini.</p>

            <div class="mt-3" style="display:flex;flex-direction:column;gap:12px;">
                <a href="dashboard.php" class="btn btn-ghost btn-block">Tetap di Sini</a>

                <form method="POST" action="logout.php">
                    <?= csrf_field() ?>
                    <button type="submit" name="logout" class="btn btn-danger btn-block">Ya, Keluar Sekarang</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

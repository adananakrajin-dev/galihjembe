<?php
// includes/header.php
// Prasyarat: session_start() sudah dipanggil oleh halaman pemanggil.
// Variabel opsional: $page_title (string), $active (kunci nav aktif)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = $page_title ?? 'SESSIONS';
$active     = $active ?? '';
$is_logged  = isset($_SESSION['sudah_login']);

// Prefix path relatif dari root situs ("" untuk halaman root, "../" untuk admin/, dst.)
if (!isset($base)) {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/') . '/';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title><?= htmlspecialchars($page_title) ?> | SESSIONS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>

<header class="site-nav">
    <div class="container nav-inner">
        <a href="<?= $base ?>index.php" class="brand">SESS<span>IONS</span></a>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= $base ?>index.php" class="<?= $active === 'home' ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="<?= $base ?>listings.php" class="<?= $active === 'katalog' ? 'active' : '' ?>">Katalog</a></li>
            <li><a href="<?= $base ?>contact.html" class="<?= $active === 'contact' ? 'active' : '' ?>">Kontak</a></li>
            <?php if (!$is_logged): ?>
                <li class="only-mobile"><a href="<?= $base ?>login.php">Masuk</a></li>
                <li class="only-mobile"><a href="<?= $base ?>register.php">Daftar</a></li>
            <?php else: ?>
                <li class="only-mobile"><a href="<?= $base ?>dashboard.php">Dashboard</a></li>
                <li class="only-mobile"><a href="<?= $base ?>orders.php">Pesanan</a></li>
                <li class="only-mobile"><a href="<?= $base ?>logout.php">Keluar</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <?php if (!$is_logged): ?>
                <a href="<?= $base ?>login.php" class="btn btn-ghost btn-sm">Masuk</a>
                <a href="<?= $base ?>register.php" class="btn btn-primary btn-sm">Daftar</a>
            <?php else: ?>
                <a href="<?= $base ?>dashboard.php" class="btn btn-ghost btn-sm">Dashboard</a>
                <a href="<?= $base ?>logout.php" class="btn btn-soft btn-sm">Keluar</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="navLinks">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
    </div>
</header>

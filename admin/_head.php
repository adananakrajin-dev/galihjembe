<?php
// admin/_head.php — pembuka halaman admin (wajib role admin).
// Variabel opsional: $page_title, $admin_active (kunci subnav aktif)

$base = '../';
include_once __DIR__ . '/../database.php';
include_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$page_title  = $page_title ?? 'Admin';
$admin_active = $admin_active ?? '';

include __DIR__ . '/../includes/header.php';

$admin_nav = [
    'index'      => ['Ringkasan',         'index.php'],
    'sellers'    => ['Approval Seller',   'sellers.php'],
    'listings'   => ['Moderasi Listing',  'listings.php'],
    'orders'     => ['Verifikasi Bayar',  'orders.php'],
    'users'      => ['Pengguna',          'users.php'],
    'categories' => ['Kategori',          'categories.php'],
    'reports'    => ['Laporan',           'reports.php'],
];
?>
<div class="subnav">
    <div class="container subnav-inner">
        <?php foreach ($admin_nav as $key => $nav): ?>
            <a href="<?= $nav[1] ?>" class="<?= $admin_active === $key ? 'active' : '' ?>"><?= $nav[0] ?></a>
        <?php endforeach; ?>
    </div>
</div>

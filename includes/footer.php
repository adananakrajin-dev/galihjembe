<?php // includes/footer.php — penutup halaman. Panggil setelah konten utama. // $base dihitung oleh header.php bila belum ada. ?>
<footer class="site-footer">
    <div class="container footer-inner">
        <span>&copy; 2026 SESSIONS &mdash; Marketplace Jasa Web &amp; Produk</span>
        <nav>
            <a href="<?= $base ?? '' ?>index.php">Beranda</a>
            <a href="<?= $base ?? '' ?>listings.php">Katalog</a>
            <a href="<?= $base ?? '' ?>contact.html">Kontak</a>
        </nav>
    </div>
</footer>

<script src="<?= $base ?? '' ?>assets/js/wilayah.js"></script>
<script src="<?= $base ?? '' ?>assets/js/main.js"></script>
</body>
</html>

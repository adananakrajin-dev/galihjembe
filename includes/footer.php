<?php // includes/footer.php — penutup halaman. Panggil setelah konten utama. // $base dihitung oleh header.php bila belum ada. ?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= $base ?? '' ?>index.php" class="brand">SESS<span>IONS</span></a>
                <p>Marketplace jasa web &amp; produk digital. Pesan aman, hasil rapi, dukungan lokal.</p>
            </div>

            <nav class="footer-col" aria-label="Marketplace">
                <h4>Marketplace</h4>
                <ul>
                    <li><a href="<?= $base ?? '' ?>listings.php">Katalog</a></li>
                    <li><a href="<?= $base ?? '' ?>become-seller.php">Jadi Penjual</a></li>
                    <li><a href="<?= $base ?? '' ?>news.html">Berita</a></li>
                    <li><a href="<?= $base ?? '' ?>portofolio.html">Portofolio</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Akun dan bantuan">
                <h4>Akun &amp; Bantuan</h4>
                <ul>
                    <li><a href="<?= $base ?? '' ?>login.php">Masuk</a></li>
                    <li><a href="<?= $base ?? '' ?>register.php">Daftar</a></li>
                    <li><a href="<?= $base ?? '' ?>orders.php">Pesanan Saya</a></li>
                    <li><a href="<?= $base ?? '' ?>contact.html">Kontak</a></li>
                </ul>
            </nav>
        </div>

        <div class="footer-bottom">
            <span>&copy; 2026 SESSIONS &mdash; Marketplace Jasa Web &amp; Produk</span>
        </div>
    </div>
</footer>

<script src="<?= $base ?? '' ?>assets/js/wilayah.js"></script>
<script src="<?= $base ?? '' ?>assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>

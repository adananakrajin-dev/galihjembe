/* SESSIONS — JS minimal: hamburger menu + navbar solid saat scroll. */
(function () {
    'use strict';

    // Toggle menu mobile
    var toggle = document.querySelector('.nav-toggle');
    var links = document.querySelector('.nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', function () {
            var open = links.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        // Tutup menu setelah link diklik
        links.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') {
                links.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Navbar dapat latar solid saat scroll
    var nav = document.querySelector('.site-nav');
    if (nav) {
        var onScroll = function () {
            nav.classList.toggle('nav-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // Konfirmasi aksi hapus (dipakai form delete)
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
        });
    });
})();

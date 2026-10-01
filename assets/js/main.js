/* SESSIONS — JS minimal: hamburger menu + navbar solid saat scroll,
   konfirmasi modal & toast in-app (pengganti dialog browser). */
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

    /* ══════════════════════════════════════════════════════════
       Konfirmasi in-app — pengganti dialog browser "localhost says"
       ══════════════════════════════════════════════════════════ */
    var box = null;

    function ensureBox() {
        if (box) return box;
        var el = document.createElement('div');
        el.className = 'confirm-overlay';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-modal', 'true');
        el.setAttribute('aria-labelledby', 'confirmMsg');
        el.innerHTML =
            '<div class="confirm-panel">' +
              '<p class="confirm-msg" id="confirmMsg"></p>' +
              '<div class="confirm-actions">' +
                '<button type="button" class="btn btn-ghost" data-act="cancel">Batal</button>' +
                '<button type="button" class="btn btn-primary" data-act="ok">Lanjutkan</button>' +
              '</div>' +
            '</div>';
        document.body.appendChild(el);
        box = {
            el: el,
            msg: el.querySelector('.confirm-msg'),
            ok: el.querySelector('[data-act="ok"]'),
            cancel: el.querySelector('[data-act="cancel"]'),
            resolve: null
        };
        el.addEventListener('click', function (e) {
            if (e.target === el || e.target === box.cancel) closeBox(false);
            else if (e.target === box.ok) closeBox(true);
        });
        document.addEventListener('keydown', function (e) {
            if (box.resolve && e.key === 'Escape') { e.preventDefault(); closeBox(false); }
        });
        return box;
    }

    function closeBox(result) {
        if (!box || !box.resolve) return; // sudah tertutup / belum ada permintaan
        var resolve = box.resolve;
        box.resolve = null;
        box.el.classList.remove('open');
        resolve(result);
    }

    function askConfirm(message) {
        var b = ensureBox();
        b.msg.textContent = message;
        // Tombol aksi mengikuti kata kerja pertama pesan; merah untuk aksi destruktif
        var first = (message.trim().split(/\s+/)[0] || '').replace(/[?,]/g, '');
        var destructive = /(hapus|tolak|batal|cabut|lepas)/i.test(message);
        b.ok.textContent = first && first.length <= 14
            ? first.charAt(0).toUpperCase() + first.slice(1)
            : 'Lanjutkan';
        b.ok.className = 'btn ' + (destructive ? 'btn-danger' : 'btn-primary');
        b.el.classList.add('open');
        b.cancel.focus(); // fokus aman: Enter tidak langsung mengonfirmasi
        return new Promise(function (resolve) { b.resolve = resolve; });
    }

    // Semua form dengan data-confirm → modal in-app.
    // Catatan: server membaca nama tombol (aksi/simpan/...) — jadi submit ulang
    // wajib lewat requestSubmit(submitter) supaya nilai tombol & validasi HTML terkirim.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('click', function (e) {
            var btn = e.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
            if (btn && form.contains(btn)) form._lastSubmitter = btn;
        }, true);

        form.addEventListener('submit', function (e) {
            if (form._confirmed) { form._confirmed = false; return; } // lolos setelah konfirmasi
            e.preventDefault();
            var submitter = e.submitter || form._lastSubmitter || null;
            askConfirm(form.getAttribute('data-confirm') || 'Lanjutkan aksi ini?').then(function (ok) {
                if (!ok) return;
                form._confirmed = true;
                try {
                    form.requestSubmit(submitter || undefined);
                } catch (err) {
                    form.submit(); // fallback browser lama
                }
            });
        });
    });

    /* ══════════════════════════════════════════════════════════
       Toast in-app — pengganti alert kecil
       ══════════════════════════════════════════════════════════ */
    window.toast = function (message, type) {
        var t = document.createElement('div');
        t.className = 'toast' + (type ? ' toast-' + type : '');
        t.setAttribute('role', 'status');

        var msg = document.createElement('span');
        msg.className = 'toast-msg';
        msg.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Tutup notifikasi');
        close.innerHTML = '&times;';

        t.appendChild(msg);
        t.appendChild(close);
        document.body.appendChild(t);

        var timer = setTimeout(remove, 4000);
        function remove() {
            clearTimeout(timer);
            t.classList.remove('open');
            setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 250);
        }
        close.addEventListener('click', remove);
        requestAnimationFrame(function () { t.classList.add('open'); });
    };
})();

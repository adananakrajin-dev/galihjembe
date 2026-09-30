/* ============================================================
   SESSIONS — Cascade wilayah Indonesia (provinsi → kabupaten → kecamatan)
   Dataset : assets/data/wilayah.json (sumber: emsifa/api-wilayah-indonesia, MIT)
   Pakai   : bungkus field dengan <div data-wilayah="id-error-box" ...>
             - data-wilayah              : id kotak pesan error (opsional)
             - data-wilayah-block="1"    : submit form diblokir bila data gagal
             - data-wilayah-submit="change" : submit form otomatis saat pilihan
                                              berubah (filter katalog)
             - data-wilayah-ids          : "prov,kab,kec" (default:
                                              province,regency,district)
             - data-selected="..." pada <select> : nilai awal (prefill)
             - data-placeholder="..."    : teks opsi kosong
   Perilaku: data diambil sekali lalu di-cache di localStorage; bila gagal
             dimuat → pesan error tampil (dan submit diblokir bila diminta).
   ============================================================ */
(function () {
    'use strict';

    var DATA_URL = 'assets/data/wilayah.json';
    var CACHE_KEY = 'sessions_wilayah_v1';

    var dataPromise = null;
    var dataFailed = false;
    var data = null;

    function byId(id) { return document.getElementById(id); }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function loadData() {
        if (dataPromise) { return dataPromise; }
        dataPromise = new Promise(function (resolve, reject) {
            var cached = null;
            try { cached = localStorage.getItem(CACHE_KEY); } catch (e) { /* storage diblokir */ }
            if (cached) {
                try {
                    var parsed = JSON.parse(cached);
                    if (parsed && parsed.provinces) { resolve(parsed); return; }
                } catch (e) { /* rusak — ambil ulang dari server */ }
            }
            fetch(DATA_URL)
                .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
                .then(function (d) {
                    if (!d || !d.provinces) { throw new Error('format data tidak valid'); }
                    try { localStorage.setItem(CACHE_KEY, JSON.stringify(d)); } catch (e) {}
                    resolve(d);
                })
                .catch(function (err) {
                    dataFailed = true;
                    dataPromise = null; // izinkan coba lagi pada muat ulang berikutnya
                    reject(err);
                });
        });
        return dataPromise;
    }

    function showError(boxId, message) {
        var box = boxId ? byId(boxId) : null;
        if (!box) { return; }
        box.innerHTML = message
            ? '<div class="alert alert-danger" role="alert"><span>' + esc(message) + '</span></div>'
            : '';
    }

    function fill(sel, pairs, selected) {
        var placeholder = sel.getAttribute('data-placeholder') || 'Pilih Provinsi';
        var html = '<option value="">' + esc(placeholder) + '</option>';
        (pairs || []).forEach(function (p) {
            html += '<option value="' + esc(p[1]) + '">' + esc(p[1]) + '</option>';
        });
        sel.innerHTML = html;
        if (selected) {
            sel.value = selected;
            if (sel.value !== selected) { sel.value = ''; } // nilai tidak ada di daftar
        }
    }

    function provinceIdByName(name) {
        for (var i = 0; i < data.provinces.length; i++) {
            if (data.provinces[i][1] === name) { return data.provinces[i][0]; }
        }
        return null;
    }

    function regencyIdByName(pid, name) {
        var list = (data.regencies && data.regencies[pid]) || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i][1] === name) { return list[i][0]; }
        }
        return null;
    }

    function init(root) {
        var ids = (root.getAttribute('data-wilayah-ids') || 'province,regency,district').split(',');
        var p = byId(ids[0].trim());
        var r = byId((ids[1] || '').trim());
        var d = byId((ids[2] || '').trim());
        if (!p || !r) { return; }

        var errBox = root.getAttribute('data-wilayah') || '';
        var blockOnFail = root.getAttribute('data-wilayah-block') === '1';
        var submitOnChange = root.getAttribute('data-wilayah-submit') === 'change';
        var form = root.closest('form');

        // Nilai awal (prefill edit / filter aktif) — dibaca sekali lalu dibuang
        var initial = {
            province: p.getAttribute('data-selected') || '',
            regency: r.getAttribute('data-selected') || '',
            district: d ? (d.getAttribute('data-selected') || '') : ''
        };

        if (form && blockOnFail) {
            form.addEventListener('submit', function (ev) {
                if (dataFailed) {
                    ev.preventDefault();
                    showError(errBox, 'Data wilayah gagal dimuat — formulir tidak bisa disimpan. Muat ulang halaman lalu coba lagi.');
                }
            });
        }

        function reloadRegencies(keepRegency, keepDistrict) {
            var pid = p.value ? provinceIdByName(p.value) : null;
            fill(r, pid ? data.regencies[pid] : [], keepRegency || '');
            r.disabled = !pid;
            reloadDistricts(keepDistrict || '');
        }

        function reloadDistricts(keepDistrict) {
            if (!d) { return; }
            var pid = p.value ? provinceIdByName(p.value) : null;
            var rid = (pid && r.value) ? regencyIdByName(pid, r.value) : null;
            fill(d, rid ? data.districts[rid] : [], keepDistrict || '');
            d.disabled = !rid;
        }

        function afterChange() {
            if (submitOnChange && form) {
                setTimeout(function () { form.submit(); }, 0);
            }
        }

        p.addEventListener('change', function () { reloadRegencies('', ''); afterChange(); });
        r.addEventListener('change', function () { reloadDistricts(''); afterChange(); });

        // Sebelum data termuat: kunci kabupaten & kecamatan
        r.disabled = true;
        if (d) { d.disabled = true; }

        loadData().then(function (loaded) {
            data = loaded;
            fill(p, data.provinces, initial.province);
            reloadRegencies(initial.province ? initial.regency : '', initial.district);
            showError(errBox, ''); // bersihkan pesan galat lama bila ada
        }).catch(function () {
            showError(
                errBox,
                'Data wilayah gagal dimuat dari server — pilihan lokasi tidak tersedia'
                + (blockOnFail ? '; pengiriman diblokir sampai data termuat.' : '.')
            );
        });
    }

    function autoInit() {
        var roots = document.querySelectorAll('[data-wilayah]');
        Array.prototype.forEach.call(roots, init);
    }

    window.Wilayah = { init: init, loadData: loadData };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }
})();

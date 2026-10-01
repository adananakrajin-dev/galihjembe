<?php
include 'database.php';
include 'includes/functions.php';
require_login();

$error = '';
$flash_after = '';

// ── Mode pesanan nyata (?order=ID) ──
$order = null;
if (isset($_GET['order'])) {
    $order_id = (int)$_GET['order'];
    try {
        $order = db_one($db, 'SELECT * FROM orders WHERE id = ? AND buyer_id = ?',
            'ii', $order_id, (int)$_SESSION['user_id']);
    } catch (Throwable $e) {}
    if (!$order) {
        set_flash('Pesanan tidak ditemukan.', 'danger');
        header('Location: orders.php');
        exit;
    }
    if ($order['status'] === 'batal') {
        set_flash('Pesanan ini sudah dibatalkan.', 'info');
        header('Location: orders.php');
        exit;
    }
}

// ── Info pembayaran: dari tabel settings, fallback nilai default ──
$pay = [
    'wa'       => '6287867851779',
    'qris'     => 'assets/img/qr.jpeg',
    'ewallet'  => ['number' => '0878-6785-1779', 'name' => 'SESSIONS STUDIO'],
    'bank'     => [
        'BCA' => ['number' => '1234567890', 'name' => 'a.n. Nama Kamu'],
        'BNI' => ['number' => '0987654321', 'name' => 'a.n. Nama Kamu'],
        'BRI' => ['number' => '1122334455', 'name' => 'a.n. Nama Kamu'],
    ],
];
try {
    foreach (db_all($db, 'SELECT `key`, `value` FROM settings') as $s) {
        $k = $s['key'];
        if ($k === 'payment_wa')           { $pay['wa'] = $s['value']; }
        if ($k === 'payment_qris')         { $pay['qris'] = $s['value']; }
        if ($k === 'payment_ewallet_num')  { $pay['ewallet']['number'] = $s['value']; }
        if ($k === 'payment_ewallet_name') { $pay['ewallet']['name'] = $s['value']; }
        if ($k === 'payment_bca')          { $pay['bank']['BCA']['number'] = $s['value']; }
        if ($k === 'payment_bni')          { $pay['bank']['BNI']['number'] = $s['value']; }
        if ($k === 'payment_bri')          { $pay['bank']['BRI']['number'] = $s['value']; }
        if ($k === 'payment_bank_name') {
            foreach ($pay['bank'] as $bk => $v) { $pay['bank'][$bk]['name'] = $s['value']; }
        }
    }
} catch (Throwable $e) { /* tabel settings belum ada — pakai default */ }

$METHODS = ['QRIS', 'DANA', 'GOPAY', 'OVO', 'BCA', 'BNI', 'BRI'];

// ── Simpan metode pembayaran ──
if (isset($_POST['save_method'])) {
    csrf_check();
    $method = $_POST['method'] ?? '';
    if (!$order) {
        $error = 'Metode ini hanya bisa disimpan untuk pesanan dari katalog.';
    } elseif (!in_array($method, $METHODS, true)) {
        $error = 'Pilih metode pembayaran yang tersedia.';
    } else {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $db->prepare('UPDATE orders SET payment_method = ? WHERE id = ? AND buyer_id = ?');
        $stmt->bind_param('sii', $method, $order['id'], $uid);
        $stmt->execute();
        $stmt->close();
        set_flash('Metode pembayaran ' . $method . ' dipilih. Silakan transfer lalu upload bukti.', 'success');
        header('Location: checkout.php?order=' . $order['id']);
        exit;
    }
}

// ── Upload bukti pembayaran ──
if (isset($_POST['upload_proof'])) {
    csrf_check();
    if (!$order) {
        $error = 'Upload bukti hanya untuk pesanan nyata dari katalog.';
    } elseif (empty($order['payment_method'])) {
        $error = 'Pilih metode pembayaran dulu sebelum upload bukti.';
    } elseif (in_array($order['status'], ['diverifikasi', 'proses', 'selesai'], true)) {
        $error = 'Pembayaran pesanan ini sudah diverifikasi.';
    } else {
        $up = handle_upload($_FILES['proof'] ?? [], 'proof');
        if ($up['ok']) {
            delete_upload($order['payment_proof'] ?? null);
            $uid = (int)$_SESSION['user_id'];
            $stmt = $db->prepare('UPDATE orders SET payment_proof = ? WHERE id = ? AND buyer_id = ?');
            $stmt->bind_param('sii', $up['file'], $order['id'], $uid);
            $stmt->execute();
            $stmt->close();
            set_flash('Bukti pembayaran terkirim! Menunggu verifikasi admin.', 'success');
            header('Location: checkout.php?order=' . $order['id']);
            exit;
        }
        $error = $up['error'];
    }
}

// ── Data tampilan ──
if ($order) {
    $packageName  = $order['title'];
    $totalPayment = (float)$order['total'];
    $packagePrice = (int)round($totalPayment / 1.11);
    $tax          = $totalPayment - $packagePrice;
    $method       = $order['payment_method'] ?? '';
    $proof        = $order['payment_proof'] ?? null;
    $page_title   = 'Pembayaran — ' . $order['order_code'];
} else {
    $packageName  = trim($_GET['package'] ?? 'Website Package');
    $packagePrice = max(0, (int)($_GET['price'] ?? 0));
    $tax          = (int)round($packagePrice * 0.11);
    $totalPayment = $packagePrice + $tax;
    $method       = '';
    $proof        = null;
    $page_title   = 'Checkout';
}
$username = $_SESSION['username'] ?? '';

include 'includes/header.php';
?>

<main>
    <section class="page-head">
        <div class="container">
            <a class="back-link" href="<?= $order ? 'orders.php' : 'listings.php' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg>
                <?= $order ? 'Kembali ke pesanan saya' : 'Kembali ke katalog' ?>
            </a>
            <h1>Checkout</h1>
            <?php if ($order): ?>
                <p>Kode pesanan <strong style="color:var(--text)"><?= e($order['order_code']) ?></strong> —
                   pilih metode bayar, transfer, lalu upload bukti untuk verifikasi.</p>
            <?php else: ?>
                <p>Pilih metode pembayaran, lalu kirim bukti bayar ke WhatsApp kami untuk verifikasi.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="section" style="padding-top:8px;">
        <div class="container">
            <?php flash_alert(); ?>

            <?php if ($error): ?>
                <div class="alert alert-danger mb-3" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid-2" style="align-items:start;">

                <!-- Ringkasan pesanan -->
                <div>
                    <h3 class="mb-2">Ringkasan Pesanan</h3>
                    <div class="order-summary">
                        <div class="summary-row" style="color:var(--text);font-weight:600;">
                            <span><?= e($packageName) ?></span>
                            <span class="badge badge-info">Layanan Web</span>
                        </div>
                        <div class="summary-row"><span>Pemesan</span><span style="color:var(--text)"><?= e($username) ?></span></div>
                        <?php if ($order): ?>
                            <div class="summary-row"><span>Kode Pesanan</span><span><?= e($order['order_code']) ?></span></div>
                        <?php endif; ?>
                        <div class="summary-row"><span>Harga Dasar</span><span><?= rupiah($packagePrice) ?></span></div>
                        <div class="summary-row"><span>PPN (11%)</span><span><?= rupiah($tax) ?></span></div>
                        <div class="summary-row"><span>Biaya Layanan</span><span style="color:var(--success)">Gratis</span></div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span style="color:var(--accent-strong)"><?= rupiah($totalPayment) ?></span>
                        </div>
                    </div>

                    <?php if ($order): ?>
                        <div class="mt-3" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <span class="faint">Status:</span>
                            <?= order_status_badge($order) ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info mt-3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                        <span>Pembayaran diverifikasi manual oleh admin — biasanya kurang dari 1×24 jam pada jam kerja.</span>
                    </div>
                </div>

                <!-- Metode pembayaran / status -->
                <div>
                    <?php if ($proof): ?>
                        <!-- Sudah upload bukti -->
                        <h3 class="mb-2">Bukti Terkirim</h3>
                        <div class="alert alert-success mb-2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
                            <span>Bukti pembayaran sudah dikirim. Menunggu verifikasi admin.</span>
                        </div>
                        <div class="card-media" style="aspect-ratio:4/3;border-radius:12px;">
                            <img src="uploads/<?= e($proof) ?>" alt="Bukti pembayaran" style="object-fit:contain;background:#fff;">
                        </div>

                        <div class="divider"></div>

                        <h3 class="mb-2">Ganti / Tambah Bukti</h3>
                        <form action="checkout.php?order=<?= (int)$order['id'] ?>" method="POST" enctype="multipart/form-data" class="form">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="proof">File Bukti Baru</label>
                                <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                <span class="hint">JPG/PNG/WebP/PDF, maks 2MB.</span>
                            </div>
                            <button type="submit" name="upload_proof" class="btn btn-soft btn-block">Ganti Bukti</button>
                        </form>

                        <a href="orders.php" class="btn btn-primary btn-block mt-2">Lihat Status Pesanan</a>

                    <?php elseif ($method): ?>
                        <!-- Metode dipilih, tinggal transfer + upload -->
                        <h3 class="mb-2">Instruksi Pembayaran</h3>
                        <div class="order-summary mb-2">
                            <div class="summary-row"><span>Metode</span><span style="color:var(--text);font-weight:600;"><?= e($method) ?></span></div>
                        </div>

                        <button class="btn btn-soft btn-block" type="button" id="btnPay">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            Lihat Instruksi Bayar
                        </button>

                        <div class="divider"></div>

                        <form action="checkout.php?order=<?= (int)$order['id'] ?>" method="POST" enctype="multipart/form-data" class="form">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label for="proof">Upload Bukti Pembayaran</label>
                                <input id="proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                <span class="hint">Screenshot struk / foto bukti transfer. JPG/PNG/WebP/PDF, maks 2MB.</span>
                            </div>
                            <button type="submit" name="upload_proof" class="btn btn-primary btn-block">
                                Kirim Bukti Pembayaran
                            </button>
                        </form>

                        <form action="checkout.php?order=<?= (int)$order['id'] ?>" method="POST" class="mt-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="method" value="">
                            <button type="submit" name="save_method" class="btn btn-ghost btn-sm" style="width:100%;" disabled>Metode dipilih</button>
                        </form>

                    <?php elseif ($order): ?>
                        <!-- Belum pilih metode -->
                        <h3 class="mb-2">Metode Pembayaran</h3>
                        <form action="checkout.php?order=<?= (int)$order['id'] ?>" method="POST">
                            <?= csrf_field() ?>

                            <div class="group-label">QRIS &amp; E-Wallet</div>
                            <div class="pay-methods">
                                <?php foreach (['QRIS', 'DANA', 'GOPAY', 'OVO'] as $m): ?>
                                    <label class="pay-method"><input type="radio" name="method" value="<?= $m ?>" required> <?= $m ?></label>
                                <?php endforeach; ?>
                            </div>

                            <div class="group-label">Transfer Bank</div>
                            <div class="pay-methods">
                                <?php foreach (['BCA', 'BNI', 'BRI'] as $m): ?>
                                    <label class="pay-method"><input type="radio" name="method" value="<?= $m ?>" required> <?= $m ?></label>
                                <?php endforeach; ?>
                            </div>

                            <button class="btn btn-primary btn-block mt-3" type="submit" name="save_method">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                Simpan Metode &amp; Lanjutkan
                            </button>
                        </form>

                    <?php else: ?>
                        <!-- Mode demo (tanpa pesanan) -->
                        <h3 class="mb-2">Metode Pembayaran</h3>
                        <div class="group-label">QRIS &amp; E-Wallet</div>
                        <div class="pay-methods">
                            <?php foreach (['QRIS', 'DANA', 'GOPAY', 'OVO'] as $m): ?>
                                <label class="pay-method"><input type="radio" name="method" value="<?= $m ?>"> <?= $m ?></label>
                            <?php endforeach; ?>
                        </div>

                        <div class="group-label">Transfer Bank</div>
                        <div class="pay-methods">
                            <?php foreach (['BCA', 'BNI', 'BRI'] as $m): ?>
                                <label class="pay-method"><input type="radio" name="method" value="<?= $m ?>"> <?= $m ?></label>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn btn-primary btn-block mt-3" id="btnPay" type="button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            Lanjutkan Pembayaran
                        </button>

                        <div class="alert alert-info mt-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
                            <span>Ini halaman contoh. Buat pesanan dari <a href="listings.php" style="color:var(--accent-strong)">katalog</a> agar status &amp; bukti pembayaran tercatat.</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
</main>

<!-- Modal instruksi bayar -->
<div class="modal-overlay" id="payModal">
    <div class="modal">
        <button class="modal-close" type="button" id="modalClose" aria-label="Tutup">&times;</button>
        <h3 id="payTitle">Bayar via QRIS</h3>
        <p id="payDesc">Scan QR di bawah pakai aplikasi apa pun — GoPay, OVO, DANA, BCA, dll.</p>

        <div id="payBody"></div>

        <div class="total-tag">Total: <strong><?= rupiah($totalPayment) ?></strong></div>

        <a class="btn-wa" id="payWA" href="#" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 1 1 21 12z"/></svg>
            Konfirmasi via WhatsApp
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
(function () {
    'use strict';

    var WA_NUMBER  = <?= json_encode($pay['wa']) ?>;
    var TOTAL      = <?= json_encode(rupiah($totalPayment)) ?>;
    var QRIS_IMG   = <?= json_encode($pay['qris']) ?>;
    var EWALLET    = <?= json_encode($pay['ewallet']) ?>;
    var BANK       = <?= json_encode($pay['bank']) ?>;
    var ORDER_MODE = <?= $order ? 'true' : 'false' ?>;
    var METHOD     = <?= json_encode($method) ?>;
    var ORDER_CODE = <?= json_encode($order['order_code'] ?? '') ?>;

    var modal = document.getElementById('payModal');

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function waLink(m) {
        var msg = 'Halo SESSIONS! Saya ingin konfirmasi pembayaran.%0A%0A'
            + (ORDER_CODE ? 'Pesanan: *' + ORDER_CODE + '*%0A' : '')
            + 'Metode: *' + m + '*%0A'
            + 'Total: *' + encodeURIComponent(TOTAL) + '*';
        return 'https://wa.me/' + WA_NUMBER + '?text=' + msg;
    }

    function vaHtml(number, name, copyable) {
        return '<div class="va-box">'
            + '<div class="va-label">Nomor Tujuan</div>'
            + '<div class="va-number">' + esc(number) + '</div>'
            + '<div class="va-name">' + esc(name) + '</div>'
            + (copyable ? '<button class="va-copy" type="button" data-copy="' + esc(number) + '">Salin Nomor</button>' : '')
            + '</div>';
    }

    function openModal(title, desc, bodyHtml, m) {
        document.getElementById('payTitle').textContent = title;
        document.getElementById('payDesc').textContent = desc;
        document.getElementById('payBody').innerHTML = bodyHtml;
        document.getElementById('payWA').href = waLink(m);
        modal.classList.add('open');
    }

    function showMethod(m) {
        if (m === 'QRIS') {
            openModal('Bayar via QRIS',
                'Scan QR di bawah pakai aplikasi apa pun — GoPay, OVO, DANA, BCA, dll.',
                '<div class="qr-box"><img src="' + esc(QRIS_IMG) + '" alt="QRIS SESSIONS" width="190" height="190"><strong>SESSIONS STUDIO</strong></div>', m);
        } else if (['DANA', 'GOPAY', 'OVO'].indexOf(m) >= 0) {
            openModal('Bayar via ' + m,
                'Transfer ke nomor ' + m + ' berikut, lalu upload bukti di halaman ini.',
                vaHtml(EWALLET.number, EWALLET.name, false), m);
        } else if (BANK[m]) {
            openModal('Transfer ' + m,
                'Transfer ke rekening di bawah, lalu upload bukti pembayaran di halaman ini.',
                vaHtml(BANK[m].number, BANK[m].name, true), m);
        }
    }

    var btnPay = document.getElementById('btnPay');
    if (btnPay) {
        btnPay.addEventListener('click', function () {
            var m = METHOD;
            if (!ORDER_MODE) {
                var checked = document.querySelector('input[name="method"]:checked');
                if (!checked) { toast('Pilih metode pembayaran dulu ya!', 'warn'); return; }
                m = checked.value;
            }
            if (m) { showMethod(m); }
        });
    }

    function closeModal() { modal.classList.remove('open'); }
    document.getElementById('modalClose').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    var payBody = document.getElementById('payBody');
    payBody.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (!btn) return;
        var text = btn.getAttribute('data-copy').replace(/-/g, '');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () { toast('Nomor rekening berhasil disalin!'); });
        }
    });
})();
</script>

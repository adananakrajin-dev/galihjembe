<?php
session_start();
if (!isset($_SESSION["username"])) {
    $_SESSION["username"] = "Developer Handal";
}
$username = $_SESSION["username"];

// Ambil data dari URL yang dikirim catalog
$packageName  = isset($_GET['package']) ? htmlspecialchars($_GET['package']) : "Website Package";
$packagePrice = isset($_GET['price'])   ? (int)$_GET['price']               : 0;
$tax          = $packagePrice * 0.11;
$totalPayment = $packagePrice + $tax;

// ── GANTI INFO INI ──
$waNumber   = "6287867851779";   // Nomor WA kamu (awalan 62)
$qrisImg    = "qr.jpeg";        // Nama file foto QRIS kamu (taruh 1 folder sama file ini)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHECKOUT | SESSIONS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *  {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body, html {
            min-height: 100vh; /* Ganti height: 100% jadi min-height */
            display: flex; /* Tambahin ini */
            flex-direction: column; /* Tambahin ini */
            overflow-x: hidden;
            background-color: #050505;
            background-image: radial-gradient(circle at 50% 0%, #151c2c 0%, #050505 70%);
            background-attachment: fixed;
            color: #ffffff;
        }

        /* ── CURSOR SPOTLIGHT ── */
        .cursor-spotlight {
            position: fixed;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(255,255,255,0.035) 0%, transparent 70%);
            transition: opacity 0.4s ease;
            opacity: 0;
        }

        /* ── PARTICLE CANVAS ── */
        #particle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        /* ── SHIMMER SCAN LINE ── */
        .shimmer-line {
            position: fixed;
            top: -2px;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.08) 40%, rgba(255,255,255,0.18) 50%, rgba(255,255,255,0.08) 60%, transparent 100%);
            pointer-events: none;
            z-index: 1;
            animation: shimmerScan 7s ease-in-out infinite;
            opacity: 0;
        }

        @keyframes shimmerScan {
            0%   { top: -2px; opacity: 0; }
            5%   { opacity: 1; }
            95%  { opacity: 1; }
            100% { top: 100vh; opacity: 0; }
        }
         /* ── SOLID NATURAL NAVBAR (Fix Turun-Turun) ── */
        /* ── TRANSPARENT OVERLAY NAVBAR ── */
        nav {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 25px 4%; /* Padding disamain persis kaya index */
            background: transparent;
            width: 100%;
            /* Absolute dibuang biar dia jadi struktur solid yang gak bakal lompat/geser */
            animation: fadeDownModern 1s ease-out both;
        }

        /* Nav Brand (SESSIONS) 100% Konsisten sama Index */
        .nav-brand {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .nav-brand:hover {
            opacity: 0.7;
        }


        /* ── LAYOUT ── */
        .container {
            max-width: 1060px;
            width: 92%;
            margin: 10px auto 70px;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 28px;
        }

        /* ── CARD ── */
        .card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 18px;
            padding: 28px;
        }

        .card-title {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.06);
        }

        /* ── ORDER SUMMARY ── */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: rgba(255,255,255,0.55);
            margin-bottom: 13px;
        }
        .summary-row.highlight { color: #fff; font-weight: 500; font-size: 15px; }
        .summary-row.total {
            border-top: 1px solid rgba(255,255,255,0.07);
            padding-top: 16px;
            margin-top: 6px;
            font-size: 17px;
            font-weight: 700;
            color: #fff;
        }
        .summary-row.total span:last-child { color: #60a5fa; }
        .pkg-badge {
            font-size: 10px;
            background: rgba(96,165,250,0.12);
            color: #60a5fa;
            border: 1px solid rgba(96,165,250,0.2);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .pkg-desc {
            font-size: 12px;
            color: rgba(255,255,255,0.3);
            line-height: 1.7;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        /* ── PAYMENT METHODS ── */
        .group-label {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
            font-weight: 600;
            margin: 18px 0 10px;
        }

        .method-option {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255,255,255,0.015);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }
        .method-option:hover {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.12);
            transform: translateY(-1px);
        }
        .method-option.selected {
            background: rgba(96,165,250,0.06);
            border-color: #3b82f6;
        }
        .method-option input[type="radio"] { display: none; }

        .radio-circle {
            width: 17px; height: 17px;
            border: 2px solid rgba(255,255,255,0.25);
            border-radius: 50%;
            flex-shrink: 0;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .method-option.selected .radio-circle {
            border-color: #3b82f6;
            background: #3b82f6;
        }
        .method-option.selected .radio-circle::after {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #050505;
        }

        .method-name { font-size: 13px; font-weight: 500; flex: 1; }
        .method-icon { font-size: 16px; color: rgba(255,255,255,0.4); }

        /* ── PAY BUTTON ── */
        .btn-pay {
            width: 100%;
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: #fff;
            color: #050505;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            letter-spacing: 0.3px;
            transition: all 0.3s;
        }
        .btn-pay:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255,255,255,0.06);
        }
        .btn-pay:active { transform: scale(0.98); }

        /* ── MODAL OVERLAY ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(6px);
            z-index: 100;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: #0d1220;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 32px 28px;
            width: 92%;
            max-width: 420px;
            text-align: center;
            animation: modalPop 0.4s cubic-bezier(0.34, 1.4, 0.64, 1);
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 16px; right: 18px;
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            font-size: 18px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-close:hover { color: #fff; }

        .modal-icon {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin: 0 auto 16px;
        }

        .modal h3 {
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .modal p {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            line-height: 1.7;
            margin-bottom: 22px;
        }

        /* QRIS modal */
        .qris-frame {
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            width: 200px;
            margin: 0 auto 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .qris-frame img {
            width: 100%;
            border-radius: 6px;
            display: block;
        }
        .qris-frame span {
            font-size: 10px;
            color: #111;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* VA number */
        .va-box {
            background: rgba(96,165,250,0.06);
            border: 1px dashed rgba(96,165,250,0.3);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .va-label { font-size: 11px; color: rgba(255,255,255,0.4); margin-bottom: 6px; }
        .va-number {
            font-size: 22px;
            font-weight: 700;
            color: #60a5fa;
            letter-spacing: 3px;
        }
        .va-copy {
            margin-top: 8px;
            background: none;
            border: 1px solid rgba(96,165,250,0.3);
            color: #60a5fa;
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .va-copy:hover { background: rgba(96,165,250,0.1); }

        /* WA button */
        .btn-wa {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            padding: 13px;
            border-radius: 11px;
            background: #25d366;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-wa:hover {
            background: #1ebe5d;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(37,211,102,0.25);
        }

        .divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 16px 0;
        }

        .total-tag {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
            margin-bottom: 16px;
        }
        .total-tag strong { color: #fff; font-size: 16px; }

        /* ── ANIMATIONS ENTRY ── */
        nav {
            opacity: 0;
            animation: slideDown 0.7s 0.05s ease-out forwards;
        }
        .card:nth-child(1) {
            opacity: 0;
            animation: fadeUp 0.7s 0.2s ease-out forwards;
        }
        .card:nth-child(2) {
            opacity: 0;
            animation: fadeUp 0.7s 0.35s ease-out forwards;
        }
        .method-option {
            opacity: 0;
            animation: fadeUp 0.5s ease-out forwards;
        }
        .method-option:nth-child(1) { animation-delay: 0.45s; }
        .method-option:nth-child(2) { animation-delay: 0.52s; }
        .method-option:nth-child(3) { animation-delay: 0.59s; }
        .method-option:nth-child(4) { animation-delay: 0.66s; }
        .method-option:nth-child(5) { animation-delay: 0.73s; }
        .method-option:nth-child(6) { animation-delay: 0.80s; }
        .method-option:nth-child(7) { animation-delay: 0.87s; }

        .btn-pay {
            opacity: 0;
            animation: fadeUp 0.6s 0.95s ease-out forwards;
        }

        .summary-row {
            opacity: 0;
            animation: fadeUp 0.5s ease-out forwards;
        }
        .summary-row:nth-child(1) { animation-delay: 0.3s; }
        .summary-row:nth-child(2) { animation-delay: 0.38s; }
        .summary-row:nth-child(3) { animation-delay: 0.46s; }
        .summary-row:nth-child(4) { animation-delay: 0.54s; }
        .summary-row:nth-child(5) { animation-delay: 0.62s; }

        /* ── KEYFRAMES ── */
        @keyframes fadeIn   { from { opacity:0; } to { opacity:1; } }
        @keyframes slideDown { from { opacity:0; transform:translateY(-18px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeUp    { from { opacity:0; transform:translateY(22px);  } to { opacity:1; transform:translateY(0); } }
        @keyframes slideUp {
            from { opacity:0; transform: translateY(30px) scale(0.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }
        @keyframes modalPop {
            from { opacity:0; transform: translateY(40px) scale(0.94); }
            to   { opacity:1; transform: translateY(0)   scale(1); }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 820px) {
            .container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- Cursor Spotlight -->
    <div class="cursor-spotlight" id="cursorSpotlight"></div>

    <!-- Particle Canvas -->
    <canvas id="particle-canvas"></canvas>

    <!-- Shimmer Scan Line -->
    <div class="shimmer-line"></div>


    <!-- Navbar yang udah dikunci posisinya (Gak bakal lompat-lompat pas di-scroll) -->
    <nav>
        <a href="javascript:history.back()" class="nav-brand">SESSIONS</a> 
    </nav>
<div class="container">

    <!-- ── ORDER SUMMARY ── -->
    <div class="card">
        <div class="card-title">Ringkasan Pesanan</div>

        <div class="summary-row highlight">
            <span><?= $packageName ?></span>
            <span class="pkg-badge">Layanan Web</span>
        </div>
        <p class="pkg-desc">Pembuatan website custom eksklusif termasuk domain, hosting, dan integrasi CMS dashboard premium.</p>

        <div class="summary-row">
            <span>Pemesan</span>
            <span style="color:#fff;font-weight:500"><?= htmlspecialchars($username) ?></span>
        </div>
        <div class="summary-row">
            <span>Harga Dasar</span>
            <span>Rp <?= number_format($packagePrice,0,',','.') ?></span>
        </div>
        <div class="summary-row">
            <span>PPN (11%)</span>
            <span>Rp <?= number_format($tax,0,',','.') ?></span>
        </div>
        <div class="summary-row">
            <span>Biaya Layanan</span>
            <span style="color:#34d399">Gratis</span>
        </div>
        <div class="summary-row total">
            <span>Total</span>
            <span>Rp <?= number_format($totalPayment,0,',','.') ?></span>
        </div>
    </div>

    <!-- ── PAYMENT METHODS ── -->
    <div class="card">
        <div class="card-title">Metode Pembayaran</div>

        <div class="group-label">QRIS & E-Wallet</div>

        <label class="method-option" onclick="selectMethod(this, 'QRIS')">
            <input type="radio" name="method" value="QRIS">
            <div class="radio-circle"></div>
            <span class="method-name">QRIS (Scan & Pay)</span>
            <i class="fa-solid fa-qrcode method-icon"></i>
        </label>
        <label class="method-option" onclick="selectMethod(this, 'DANA')">
            <input type="radio" name="method" value="DANA">
            <div class="radio-circle"></div>
            <span class="method-name">DANA</span>
            <i class="fa-solid fa-wallet method-icon"></i>
        </label>
        <label class="method-option" onclick="selectMethod(this, 'GOPAY')">
            <input type="radio" name="method" value="GOPAY">
            <div class="radio-circle"></div>
            <span class="method-name">GoPay</span>
            <i class="fa-solid fa-mobile-screen method-icon"></i>
        </label>
        <label class="method-option" onclick="selectMethod(this, 'OVO')">
            <input type="radio" name="method" value="OVO">
            <div class="radio-circle"></div>
            <span class="method-name">OVO</span>
            <i class="fa-solid fa-coins method-icon"></i>
        </label>

        <div class="group-label">Virtual Account</div>

        <label class="method-option" onclick="selectMethod(this, 'BCA')">
            <input type="radio" name="method" value="BCA">
            <div class="radio-circle"></div>
            <span class="method-name">BCA Virtual Account</span>
            <i class="fa-solid fa-building-columns method-icon"></i>
        </label>
        <label class="method-option" onclick="selectMethod(this, 'BNI')">
            <input type="radio" name="method" value="BNI">
            <div class="radio-circle"></div>
            <span class="method-name">BNI Virtual Account</span>
            <i class="fa-solid fa-building-columns method-icon"></i>
        </label>
        <label class="method-option" onclick="selectMethod(this, 'BRI')">
            <input type="radio" name="method" value="BRI">
            <div class="radio-circle"></div>
            <span class="method-name">BRI Virtual Account</span>
            <i class="fa-solid fa-building-columns method-icon"></i>
        </label>

        <button class="btn-pay" onclick="handlePay()">
            <i class="fa-solid fa-lock"></i> Lanjutkan Pembayaran
        </button>
    </div>
</div>

<!-- ══════════════════════════════════════ -->
<!-- MODAL: QRIS                           -->
<!-- ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-QRIS">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('QRIS')"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-icon" style="background:rgba(255,255,255,0.06)">
            <i class="fa-solid fa-qrcode"></i>
        </div>
        <h3>Bayar via QRIS</h3>
        <p>Scan QR di bawah pakai aplikasi apapun — GoPay, OVO, DANA, BCA, dll.</p>

        <div class="qris-frame">
            <!-- ⬇ Ganti 'qris.jpg' dengan nama file QRIS kamu -->
            <img src="qr.jpeg" alt="QRIS SESSIONS"
                 onerror="this.outerHTML='<div style=\'width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#f0f0f0;border-radius:6px;color:#999;font-size:12px;text-align:center;padding:10px\'>Upload foto QRIS kamu ke folder yang sama dengan nama <b>qris.jpg</b></div>'">
            <span>SESSIONS STUDIO</span>
        </div>

        <div class="total-tag">Total: <strong>Rp <?= number_format($totalPayment,0,',','.') ?></strong></div>

        <a class="btn-wa" id="wa-qris" href="https://wa.me/087867851779" target="_blank">
            <i class="fa-brands fa-whatsapp"></i> Kirim Bukti ke WhatsApp
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════ -->
<!-- MODAL: E-Wallet (DANA / GOPAY / OVO)  -->
<!-- ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-EWALLET">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('EWALLET')"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-icon" style="background:rgba(96,165,250,0.08)">
            <i class="fa-solid fa-wallet" style="color:#60a5fa"></i>
        </div>
        <h3 id="ewallet-title">Bayar via E-Wallet</h3>
        <p id="ewallet-desc">Transfer ke nomor terdaftar berikut lalu kirim bukti bayar ke WhatsApp kami.</p>

        <div class="va-box">
            <div class="va-label">Nomor Tujuan</div>
            <!-- ⬇ Ganti dengan nomor e-wallet kamu -->
            <div class="va-number" id="ewallet-number">0878-6785-1779</div>
            <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:4px" id="ewallet-name">SESSIONS STUDIO</div>
        </div>

        <div class="total-tag">Total: <strong>Rp <?= number_format($totalPayment,0,',','.') ?></strong></div>
        <div class="divider"></div>

        <a class="btn-wa" id="wa-ewallet" href="https://wa.me/087867851779" target="_blank">
            <i class="fa-brands fa-whatsapp"></i> Kirim Bukti ke WhatsApp
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════ -->
<!-- MODAL: Virtual Account                -->
<!-- ══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-VA">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('VA')"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-icon" style="background:rgba(52,211,153,0.08)">
            <i class="fa-solid fa-building-columns" style="color:#34d399"></i>
        </div>
        <h3 id="va-title">Transfer Bank</h3>
        <p>Transfer ke rekening di bawah, lalu kirim bukti pembayaran ke WhatsApp kami untuk konfirmasi.</p>

        <div class="va-box">
            <div class="va-label">Nomor Rekening <span id="va-bank-label"></span></div>
            <!-- ⬇ Ganti dengan nomor rekening kamu per bank -->
            <div class="va-number" id="va-number">0000-0000-0000</div>
            <div style="font-size:12px;color:rgba(255,255,255,0.4);margin-top:4px" id="va-name">a.n. Nama Kamu</div>
            <button class="va-copy" onclick="copyVA()"><i class="fa-regular fa-copy"></i> Salin Nomor</button>
        </div>

        <div class="total-tag">Total: <strong>Rp <?= number_format($totalPayment,0,',','.') ?></strong></div>
        <div class="divider"></div>

        <a class="btn-wa" id="wa-va" href="https://wa.me/087867851779" target="_blank">
            <i class="fa-brands fa-whatsapp"></i> Kirim Bukti ke WhatsApp
        </a>
    </div>
</div>

<script>
// ── KONFIGURASI — GANTI SESUAI DATA KAMU ──
const WA_NUMBER = "<?= $waNumber ?>";
const TOTAL     = "Rp <?= number_format($totalPayment,0,',','.') ?>";

// Nomor e-wallet kamu
const EWALLET = {
    DANA:  { number: "0878-6785-1779", name: "SESSIONS STUDIO" },
    GOPAY: { number: "0878-6785-1779", name: "SESSIONS STUDIO" },
    OVO:   { number: "0878-6785-1779", name: "SESSIONS STUDIO" },
};

// Nomor rekening per bank kamu
const BANK = {
    BCA: { number: "1234567890", name: "a.n. Nama Kamu" },
    BNI: { number: "0987654321", name: "a.n. Nama Kamu" },
    BRI: { number: "1122334455", name: "a.n. Nama Kamu" },
};
// ──────────────────────────────────────────

let selectedMethod = null;
let currentVANumber = "";

function waLink(method) {
    const msg = encodeURIComponent(
        `Halo SESSIONS! Saya ingin mengirim bukti pembayaran.\n\n` +
        `Metode: *${method}*\n` +
        `Total: *${TOTAL}*\n\n` +
        `[Lampirkan screenshot bukti transfer di sini]`
    );
    return `https://wa.me/${WA_NUMBER}?text=${msg}`;
}

function selectMethod(el, method) {
    document.querySelectorAll('.method-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
    selectedMethod = method;
}

function handlePay() {
    if (!selectedMethod) {
        alert("Pilih metode pembayaran dulu ya!");
        return;
    }

    if (selectedMethod === 'QRIS') {
        document.getElementById('wa-qris').href = waLink('QRIS');
        openModal('QRIS');

    } else if (['DANA','GOPAY','OVO'].includes(selectedMethod)) {
        const d = EWALLET[selectedMethod];
        document.getElementById('ewallet-title').textContent = `Bayar via ${selectedMethod}`;
        document.getElementById('ewallet-desc').textContent =
            `Transfer ke nomor ${selectedMethod} berikut, lalu kirim bukti ke WhatsApp.`;
        document.getElementById('ewallet-number').textContent = d.number;
        document.getElementById('ewallet-name').textContent   = d.name;
        document.getElementById('wa-ewallet').href = waLink(selectedMethod);
        openModal('EWALLET');

    } else if (['BCA','BNI','BRI'].includes(selectedMethod)) {
        const d = BANK[selectedMethod];
        document.getElementById('va-title').textContent      = `Transfer ${selectedMethod}`;
        document.getElementById('va-bank-label').textContent = selectedMethod;
        document.getElementById('va-number').textContent     = d.number;
        document.getElementById('va-name').textContent       = d.name;
        document.getElementById('wa-va').href = waLink(selectedMethod);
        currentVANumber = d.number;
        openModal('VA');
    }
}

function openModal(id)  { document.getElementById('modal-' + id).classList.add('open'); }
function closeModal(id) { document.getElementById('modal-' + id).classList.remove('open'); }

// Tutup modal kalau klik di luar
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

function copyVA() {
    navigator.clipboard.writeText(currentVANumber.replace(/-/g,''))
        .then(() => alert('Nomor rekening berhasil disalin!'));
}

 /* ── 1. CURSOR SPOTLIGHT ── */
        const spotlight = document.getElementById('cursorSpotlight');
        let spotX = window.innerWidth / 2, spotY = window.innerHeight / 2;
        let currentX = spotX, currentY = spotY;
        let spotVisible = false;

        document.addEventListener('mousemove', (e) => {
            spotX = e.clientX;
            spotY = e.clientY;
            if (!spotVisible) {
                spotlight.style.opacity = '1';
                spotVisible = true;
            }
        });

        document.addEventListener('mouseleave', () => {
            spotlight.style.opacity = '0';
            spotVisible = false;
        });

        function animateSpotlight() {
            currentX += (spotX - currentX) * 0.07;
            currentY += (spotY - currentY) * 0.07;
            spotlight.style.left = currentX + 'px';
            spotlight.style.top = currentY + 'px';
            requestAnimationFrame(animateSpotlight);
        }
        animateSpotlight();

        /* ── 2. AMBIENT PARTICLES ── */
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const particles = [];
        const PARTICLE_COUNT = 55;

        for (let i = 0; i < PARTICLE_COUNT; i++) {
            particles.push({
                x: Math.random() * window.innerWidth,
                y: Math.random() * window.innerHeight,
                r: Math.random() * 1.2 + 0.3,
                alpha: Math.random() * 0.35 + 0.05,
                vx: (Math.random() - 0.5) * 0.18,
                vy: (Math.random() - 0.5) * 0.18,
                pulse: Math.random() * Math.PI * 2,
                pulseSpeed: Math.random() * 0.012 + 0.006
            });
        }

        function drawParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.pulse += p.pulseSpeed;

                if (p.x < -5) p.x = canvas.width + 5;
                if (p.x > canvas.width + 5) p.x = -5;
                if (p.y < -5) p.y = canvas.height + 5;
                if (p.y > canvas.height + 5) p.y = -5;

                const dynamicAlpha = p.alpha * (0.6 + 0.4 * Math.sin(p.pulse));

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${dynamicAlpha})`;
                ctx.fill();
            });
            requestAnimationFrame(drawParticles);
        }
        drawParticles();

        /* ── 3. MAGNETIC ICON pada stat-box ── */
        document.querySelectorAll('.stat-box').forEach(box => {
            const icon = box.querySelector('i');

            box.addEventListener('mousemove', (e) => {
                const rect = box.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                const dx = (e.clientX - cx) * 0.28;
                const dy = (e.clientY - cy) * 0.28;
                if (icon) icon.style.transform = `translate(${dx}px, ${dy}px) scale(1.15)`;
            });
            box.addEventListener('mouseleave', () => {
                if (icon) icon.style.transform = 'translate(0, 0) scale(1)';
            });
        });

        /* ── 4. CHARACTER REVEAL pada H1 ── */
        const heroTitle = document.getElementById('heroTitle');
        const titleText = heroTitle.textContent;
        heroTitle.textContent = '';

        titleText.split('').forEach((char, i) => {
            const span = document.createElement('span');
            span.classList.add('char');
            span.textContent = char === ' ' ? '\u00A0' : char;
            span.style.animationDelay = (0.4 + i * 0.07) + 's';
            heroTitle.appendChild(span);
        });

        /* ── 5. TYPEWRITER pada SUBTITLE ── */
        const subtitleEl = document.getElementById('subtitle');
        const subtitleText = 'Accelerating the World\'s Transition to High-End Digital Presence';
        let charIndex = 0;

        function typeWriter() {
            if (charIndex < subtitleText.length) {
                subtitleEl.textContent += subtitleText[charIndex];
                charIndex++;
                setTimeout(typeWriter, 32);
            } else {
                subtitleEl.classList.add('done');
            }
        }

        setTimeout(typeWriter, 1600);
</script>
</body>
</html>
<?php
// includes/functions.php — helper autentikasi, CSRF, dan utilitas.

if (session_status() === PHP_SESSION_NONE) {
    // Hardening cookie sesi: HttpOnly (tak bisa dibaca JS) + SameSite (mitigasi CSRF)
    if (PHP_SAPI !== 'cli') {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
    session_start();
}

// Header keamanan dasar (selama belum ada output)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/** Escape output (anti-XSS). */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Ambil (dan buat bila perlu) token CSRF untuk sesi ini. */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Sisipkan input hidden token CSRF ke dalam form. */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validasi token CSRF untuk request POST. Panggil di awal pemrosesan form. */
function csrf_check(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { return; }
    $sent = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        die('Permintaan tidak valid (token keamanan kedaluwarsa). Silakan muat ulang halaman dan coba lagi.');
    }
}

function is_logged(): bool { return isset($_SESSION['sudah_login']); }

/**
 * Prefix ROOT situs ("" untuk project di docroot, "/bisnis/" bila di subfolder).
 * Halaman di folder admin/ dihitung satu level di atasnya → selalu menuju root.
 */
function root_url(string $path = ''): string
{
    static $root = null;
    if ($root === null) {
        // Normalisasi SETELAH dirname() — dirname() di Windows bisa menghasilkan backslash
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if (basename($dir) === 'admin') {     // halaman admin → naik ke root
            $dir = dirname($dir);
        }
        $dir = str_replace('\\', '/', $dir);
        $root = ($dir === '/' || $dir === '.' || $dir === '') ? '/' : rtrim($dir, '/') . '/';
    }
    return $root . $path;
}

/** Sinkronkan role/status user dari DB ke sesi (role bisa diubah admin). */
function sync_role(): void
{
    global $db;
    if (!isset($_SESSION['user_id']) || !($db instanceof mysqli)) { return; }
    try {
        $row = db_one($db, 'SELECT role, status, username FROM users WHERE id = ?', 'i', (int)$_SESSION['user_id']);
        if (!$row) { return; }
        if ($row['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ' . root_url('login.php?out=1'));
            exit;
        }
        $_SESSION['role']    = $row['role'];
        $_SESSION['username'] = $row['username'];
    } catch (Throwable $e) {
        // Tabel belum ada — biarkan sesi apa adanya
    }
}

/** Guard: wajib login, selain itu redirect. */
function require_login(string $redirect = 'login.php'): void {
    if (!is_logged()) { header('Location: ' . root_url($redirect)); exit; }
    sync_role();
}

/** Role pengguna saat ini: admin | seller | buyer. */
function current_role(): string { return $_SESSION['role'] ?? 'buyer'; }
function is_admin(): bool { return current_role() === 'admin'; }

/** Guard: wajib login dengan role tertentu. */
function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        header('Location: ' . root_url('dashboard.php?akses=ditolak'));
        exit;
    }
}

/** Simpan pesan flash untuk tampilan berikutnya. */
function set_flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

/** Ambil (sekali pakai) pesan flash. */
function take_flash(): ?array {
    if (empty($_SESSION['flash'])) { return null; }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

/** Render alert flash jika ada. */
function flash_alert(): void {
    $f = take_flash();
    if (!$f) { return; }
    $type = in_array($f['type'], ['danger', 'success', 'info'], true) ? $f['type'] : 'info';
    echo '<div class="alert alert-' . $type . ' mb-3" role="alert">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>'
        . '<span>' . e($f['msg']) . '</span></div>';
}

/** Format rupiah. */
function rupiah($n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

/** Query helper: ambil semua baris. */
function db_all(mysqli $db, string $sql, string $types = '', ...$args): array {
    $stmt = $db->prepare($sql);
    if ($types !== '') { $stmt->bind_param($types, ...$args); }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Query helper: ambil satu baris (null bila tidak ada). */
function db_one(mysqli $db, string $sql, string $types = '', ...$args): ?array {
    $stmt = $db->prepare($sql);
    if ($types !== '') { $stmt->bind_param($types, ...$args); }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/* ══════════════════════════════════════════════════════════
   Upload file aman (foto listing & bukti bayar)
   Maks 2MB, hanya JPG/PNG/WebP, nama acak, disimpan di uploads/
   ══════════════════════════════════════════════════════════ */

const UPLOAD_DIR = __DIR__ . '/../uploads';
const UPLOAD_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

/**
 * Proses upload. Return: ['ok' => true, 'file' => nama] atau ['ok' => false, 'error' => pesan].
 * $allowed: 'image' (foto) atau 'proof' (bukti bayar: image/PDF).
 */
function handle_upload(array $file, string $kind = 'image'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'File tidak valid.'];
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Belum ada file yang dipilih.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload gagal (kode ' . $file['error'] . ').'];
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Ukuran file maksimal 2 MB.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

    $map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if ($kind === 'proof') {
        $map['application/pdf'] = 'pdf';
    }

    if (!isset($map[$mime])) {
        return ['ok' => false, 'error' => 'Format file tidak didukung (pakai JPG/PNG/WebP'
            . ($kind === 'proof' ? ' atau PDF' : '') . ').'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $name = ($kind === 'proof' ? 'bukti_' : 'listing_') . bin2hex(random_bytes(8)) . '.' . $map[$mime];
    $dest = UPLOAD_DIR . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Gagal menyimpan file.'];
    }
    @chmod($dest, 0644);

    return ['ok' => true, 'file' => $name];
}

/** Hapus file unggahan (best effort). */
function delete_upload(?string $filename): void {
    if (!$filename || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) { return; }
    $path = UPLOAD_DIR . '/' . $filename;
    if (is_file($path)) { @unlink($path); }
}

/* ══════════════════════════════════════════════════════════
   Badge status pesanan (manual payment flow)
   ══════════════════════════════════════════════════════════ */

function order_status_badge(array $o): string
{
    $proof  = !empty($o['payment_proof']);
    $status = $o['status'] ?? '';

    $map = [
        'menunggu_bukti' => $proof
            ? ['Menunggu Verifikasi', 'badge-pending']
            : ['Menunggu Pembayaran', 'badge-pending'],
        'diverifikasi'   => ['Terverifikasi', 'badge-info'],
        'proses'         => ['Diproses', 'badge-info'],
        'selesai'        => ['Selesai', 'badge-available'],
        'batal'          => ['Dibatalkan', 'badge-sold'],
    ];

    [$label, $class] = $map[$status] ?? ['Tidak Diketahui', 'badge-muted'];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/* ══════════════════════════════════════════════════════════
   Render kartu listing (dipakai katalog, favorit, dashboard)
   ══════════════════════════════════════════════════════════ */

const ICON_SERVICE = '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M3 9h18M8 21h8"/></svg>';
const ICON_PRODUCT  = '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 7h12l1 12H5L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>';

/** Badge status listing. */
function status_badge(array $l): string {
    if (($l['status'] ?? '') === 'sold') {
        return '<span class="badge badge-sold">Terjual</span>';
    }
    if (($l['moderation'] ?? '') === 'pending') {
        return '<span class="badge badge-pending">Menunggu Moderasi</span>';
    }
    if (($l['status'] ?? '') === 'inactive') {
        return '<span class="badge badge-muted">Nonaktif</span>';
    }
    return '<span class="badge badge-available">Tersedia</span>';
}

/**
 * Validasi cascade wilayah (provinsi → kabupaten → kecamatan) terhadap dataset
 * assets/data/wilayah.json. Semua kosong dianggap valid (field opsional);
 * bila salah satu terisi, ketiganya wajib lengkap dan konsisten.
 * @return bool
 */
function wilayah_check(string $province, string $regency, string $district): bool
{
    if ($province === '' && $regency === '' && $district === '') { return true; }
    if ($province === '' || $regency === '' || $district === '') { return false; }

    static $data = null;
    if ($data === null) {
        $path = __DIR__ . '/../assets/data/wilayah.json';
        $raw  = @file_get_contents($path);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        $data = (is_array($decoded) && isset($decoded['provinces'])) ? $decoded : false;
    }
    if ($data === false) { return false; } // dataset belum tersedia

    $pid = null;
    foreach ($data['provinces'] as $p) {
        if (($p[1] ?? '') === $province) { $pid = $p[0]; break; }
    }
    if ($pid === null) { return false; }

    $rid = null;
    foreach (($data['regencies'][$pid] ?? []) as $r) {
        if (($r[1] ?? '') === $regency) { $rid = $r[0]; break; }
    }
    if ($rid === null) { return false; }

    foreach (($data['districts'][$rid] ?? []) as $d) {
        if (($d[1] ?? '') === $district) { return true; }
    }
    return false;
}

/** Render satu kartu listing. Kolom yang dibutuhkan: id, title, price, type, status, moderation, image (opsional), category_name (opsional), store_name (opsional), subtype_name (opsional), brand_name (opsional). */
function listing_card(array $l, string $base = ''): string {
    $img = !empty($l['image'])
        ? '<img src="' . e($base . 'uploads/' . $l['image']) . '" alt="' . e($l['title']) . '" loading="lazy">'
        : ($l['type'] === 'service' ? ICON_SERVICE : ICON_PRODUCT);

    $meta = [];
    $meta[] = '<span class="badge ' . ($l['type'] === 'service' ? 'badge-info' : 'badge-muted') . '">'
            . ($l['type'] === 'service' ? 'Jasa' : 'Produk') . '</span>';
    if (!empty($l['category_name'])) {
        $cat_label = $l['category_name'];
        if (!empty($l['subtype_name'])) {
            $cat_label .= ' · ' . $l['subtype_name'];
        }
        $meta[] = '<span>' . e($cat_label) . '</span>';
    }
    if (!empty($l['brand_name'])) {
        $meta[] = '<span class="card-brand">' . e($l['brand_name']) . '</span>';
    }
    if (!empty($l['store_name'])) {
        $meta[] = '<span>' . e($l['store_name']) . '</span>';
    }

    return '<a class="card" href="' . e($base) . 'listing-detail.php?id=' . (int)$l['id'] . '">'
        . '<div class="card-media">' . $img . '</div>'
        . '<div class="card-body">'
        .   '<div class="card-title">' . e($l['title']) . '</div>'
        .   '<div class="card-price">' . rupiah($l['price']) . '</div>'
        .   '<div class="card-meta">' . status_badge($l) . implode('', $meta) . '</div>'
        . '</div></a>';
}

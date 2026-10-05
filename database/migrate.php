<?php
/**
 * SESSIONS — Migrasi/verifikasi database (PostgreSQL, idempotent).
 *
 * Pakai:
 *   - CLI   : php database/migrate.php
 *   - CLI   : php database/migrate.php --seed-admin
 *   - Local : buka http://localhost/bisnis/database/migrate.php
 *
 * Yang dilakukan:
 *   1. Menjalankan seluruh statement di database/schema.sql
 *      (CREATE TABLE IF NOT EXISTS + trigger + seed idempotent)
 *   2. Verifikasi tabel & kolom inti, serta trigger pembarui timestamp
 *   3. Membuat akun admin bila belum ada — HANYA dengan flag --seed-admin.
 *      Password di-generate acak dan ditampilkan sekali, atau ambil dari env
 *      SESSIONS_ADMIN_PASSWORD. Tidak ada password default yang bisa ditebak.
 *
 * Script ini tidak menghapus atau mengubah data yang sudah ada.
 */

// ── Penjaga: hanya CLI atau akses lokal ──
if (PHP_SAPI !== 'cli') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        die('Hanya bisa dijalankan dari localhost atau CLI.');
    }
}

require __DIR__ . '/../database.php';

$seedAdmin = in_array('--seed-admin', $argv ?? [], true);

echo "=== SESSIONS DB Migrasi (PostgreSQL) ===\n";
echo "Database: {$database_name} @ {$db_host}\n\n";

$ok = 0; $fail = 0;

// ── 1. Jalankan schema.sql ──
// Pemecah statement: hormati $$ ... $$ (body fungsi plpgsql) dan literal '...'.
$sqlFile = __DIR__ . '/schema.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Gagal membaca schema.sql\n");
    exit(1);
}

function split_sql(string $sql): array
{
    $out = []; $cur = ''; $len = strlen($sql);
    $i = 0; $inS = false; $inD = false; $dollar = null;
    while ($i < $len) {
        $ch = $sql[$i]; $next = $i + 1 < $len ? $sql[$i + 1] : '';
        // tag dollar-quoted: $$ atau $nama$
        if ($dollar === null && !$inS && !$inD && $ch === '$'
            && preg_match('/\$[A-Za-z_0-9]*\$/', substr($sql, $i), $m)) {
            $dollar = $m[0];
            $cur .= $dollar; $i += strlen($dollar); continue;
        }
        if ($dollar !== null) {
            if (substr($sql, $i, strlen($dollar)) === $dollar) {
                $cur .= $dollar; $i += strlen($dollar); $dollar = null; continue;
            }
            $cur .= $ch; $i++; continue;
        }
        if (!$inD && $ch === "'") { $inS = !$inS; $cur .= $ch; $i++; continue; }
        if (!$inS && $ch === '"') { $inD = !$inD; $cur .= $ch; $i++; continue; }
        // komentar baris
        if (!$inS && !$inD && $ch === '-' && $next === '-') {
            while ($i < $len && $sql[$i] !== "\n") { $i++; }
            continue;
        }
        if (!$inS && !$inD && $ch === ';') {
            $t = trim($cur);
            if ($t !== '') { $out[] = $t; }
            $cur = ''; $i++; continue;
        }
        $cur .= $ch; $i++;
    }
    $t = trim($cur);
    if ($t !== '') { $out[] = $t; }
    return $out;
}

$statements = split_sql($sql);

foreach ($statements as $i => $stmt) {
    try {
        $db->query($stmt);
        $ok++;
    } catch (Throwable $e) {
        $fail++;
        echo "[SQL #" . ($i + 1) . "] GAGAL: " . substr($e->getMessage(), 0, 200) . "\n";
        echo "         " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 140) . "...\n";
    }
}

echo "Schema   : {$ok} pernyataan OK, {$fail} gagal\n";

// ── 2. Verifikasi tabel & kolom inti ──
$expected = [
    'users'            => ['id','name','username','password','email','role','status','created_at'],
    'categories'       => ['id','name','slug','type'],
    'listing_subtypes' => ['id','category_id','name','slug'],
    'listing_brands'   => ['id','subtype_id','name','slug'],
    'seller_profiles'  => ['id','user_id','store_name','approval','home_address'],
    'briefs'           => ['id','buyer_id','seller_id','title','status'],
    'listings'         => ['id','seller_id','title','price','condition','category_id','subtype_id','brand_id','moderation','status'],
    'listing_images'   => ['id','listing_id','image_url','is_primary'],
    'listing_packages' => ['id','listing_id','name','price'],
    'orders'           => ['id','order_code','buyer_id','seller_id','total','status'],
    'reviews'          => ['id','order_id','rating','reply','reply_updated_at'],
    'favorites'        => ['id','user_id','listing_id'],
    'reports'          => ['id','reporter_id','listing_id','review_id','reason','status'],
    'settings'         => ['key','value'],
];

$missing = 0;
foreach ($expected as $table => $cols) {
    $rows = $db->queryParams(
        "SELECT column_name FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = ?",
        [$table]
    );
    $have = array_column($rows->fetch_all(), 'column_name');
    if (!$have) {
        echo "[VERIFIKASI] tabel `{$table}` TIDAK ADA\n";
        $missing++;
        continue;
    }
    $gap = array_diff($cols, $have);
    if ($gap) {
        echo "[VERIFIKASI] tabel `{$table}` kurang kolom: " . implode(', ', $gap) . "\n";
        $missing++;
    }
}

if ($missing === 0) {
    echo "Verifikasi: semua " . count($expected) . " tabel & kolom inti lengkap.\n";
} else {
    echo "Verifikasi: {$missing} tabel/kolom bermasalah.\n";
    $fail += $missing;
}

// ── 3. Trigger pembarui timestamp ──
$trig = 0;
foreach (['listings', 'orders', 'seller_profiles', 'briefs'] as $t) {
    $n = $db->queryParams(
        "SELECT COUNT(*) AS c FROM pg_trigger
         WHERE tgrelid = to_regclass(?) AND NOT tgisinternal",
        ['public.' . $t]
    )->fetch_column();
    if ((int)$n > 0) { $trig++; } else { echo "[TRIGGER] {$t}: pemicu updated_at belum ada\n"; }
}
echo "Trigger   : {$trig}/4 tabel terpasang.\n";

// ── 4. Seed admin (hanya bila diminta) ──
$existing = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_column();

if ($existing) {
    echo "Admin     : sudah ada (id={$existing}).\n";
} elseif (!$seedAdmin) {
    echo "Admin     : belum ada. Jalankan 'php database/migrate.php --seed-admin' bila dibutuhkan.\n";
} else {
    $password = getenv('SESSIONS_ADMIN_PASSWORD') ?: null;
    $generated = false;
    if ($password === false || $password === null || $password === '') {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 20; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $generated = true;
    }

    $username = 'admin';
    $suffix = 1;
    while ($db->queryParams('SELECT id FROM users WHERE username = ?', [$username])->fetch_column()) {
        $username = 'admin' . (++$suffix);
    }

    $email = 'admin@sessions.local';
    $stmt = $db->prepare(
        'INSERT INTO users (name, username, password, email, role, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param('sssss', 'Administrator', $username, $hash, $email, 'admin', 'active');
    $stmt->execute();

    echo "\n=== Akun admin dibuat ===\n";
    echo "username: {$username}\n";
    echo "email   : {$email}\n";
    if ($generated) {
        echo "password: {$password}   <- tampil SEKALI, simpan sekarang\n";
    } else {
        echo "password: (dari SESSIONS_ADMIN_PASSWORD)\n";
    }
    echo "Ganti password setelah login.\n";
}

echo "\n=== Selesai ===\n";
echo ($fail === 0)
    ? "Semua langkah berhasil.\n"
    : "{$fail} langkah bermasalah (lihat pesan di atas).\n";

if (PHP_SAPI !== 'cli') {
    echo "\n<a href=\"../index.php\">\xE2\x86\x90 Kembali ke beranda</a>";
}

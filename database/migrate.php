<?php
/**
 * SESSIONS — Migrasi database (idempotent, aman dijalankan berulang).
 *
 * Pakai:
 *   - CLI   : php database/migrate.php
 *   - Local : buka http://localhost/bisnis/database/migrate.php
 *
 * Yang dilakukan:
 *   1. Menjalankan seluruh statement di database/schema.sql
 *      (CREATE TABLE IF NOT EXISTS + seed kategori & pengaturan)
 *   2. Menambahkan kolom baru pada tabel `users` lama bila ada
 *      (name, email, phone, location, avatar, role, status, dll.)
 *   3. Membuat akun admin default bila belum ada (admin / admin123)
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

echo "=== SESSIONS DB Migrasi ===\n";
echo "Database: " . ($database_name ?? '-') . "\n\n";

$ok = 0; $fail = 0; $skip = 0;

// ── 1. Jalankan schema.sql ──
$sqlFile = __DIR__ . '/schema.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Gagal membaca schema.sql\n");
    exit(1);
}

// Buang komentar baris (-- ...) lalu pecah per pernyataan
$lines = array_filter(explode("\n", $sql), function ($l) {
    return strpos(ltrim($l), '--') !== 0;
});
$statements = explode(';', implode("\n", $lines));

foreach ($statements as $i => $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') { $skip++; continue; }
    try {
        $db->query($stmt);
        $ok++;
    } catch (Throwable $e) {
        // INSERT IGNORE / CREATE IF NOT EXISTS seharusnya tidak gagal;
        // catat bila terjadi agar terlihat di laporan.
        $fail++;
        echo "[SQL #" . ($i + 1) . "] GAGAL: " . substr($e->getMessage(), 0, 160) . "\n";
        echo "         " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 120) . "...\n";
    }
}

echo "Schema   : {$ok} pernyataan OK, {$fail} gagal, {$skip} kosong\n";

// ── 2. Kolom tambahan untuk tabel users lama ──
$wanted = [
    'name'     => "ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `id`",
    'email'    => "ADD COLUMN `email` VARCHAR(150) NULL AFTER `name`",
    'phone'    => "ADD COLUMN `phone` VARCHAR(25) NULL AFTER `password`",
    'location' => "ADD COLUMN `location` VARCHAR(100) NULL AFTER `phone`",
    'avatar'   => "ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `location`",
    'role'     => "ADD COLUMN `role` ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer' AFTER `avatar`",
    'status'   => "ADD COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `role`",
    'created_at' => "ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`",
    'updated_at' => "ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`",
];

$added = 0;
try {
    $res = $db->query("SHOW COLUMNS FROM users");
    $existing = [];
    while ($row = $res->fetch_assoc()) { $existing[$row['Field']] = true; }

    foreach ($wanted as $col => $ddl) {
        if (isset($existing[$col])) { continue; }
        try {
            $db->query("ALTER TABLE users " . $ddl);
            $added++;
            echo "users: kolom `{$col}` ditambahkan\n";
        } catch (Throwable $e) {
            echo "users: gagal menambah kolom `{$col}` — {$e->getMessage()}\n";
            $fail++;
        }
    }

    // Unique email bila memungkinkan (gagal diabaikan bila ada duplikat)
    if (!isset($existing['email'])) {
        try {
            $db->query('ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)');
        } catch (Throwable $e) { /* ada duplikat — biarkan, aplikasi tetap cek manual */ }
    }
} catch (Throwable $e) {
    echo "Cek tabel users gagal: {$e->getMessage()}\n";
    $fail++;
}

// ── 3. Akun admin default ──
try {
    $res = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($res && $res->num_rows === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, username, password, role, status)
             VALUES ('Administrator', 'admin@sessions.local', 'admin', ?, 'admin', 'active')"
        );
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
        echo "\nAkun admin dibuat → username: admin | password: admin123 (GANTI setelah masuk!)\n";
    } else {
        echo "Akun admin sudah ada.\n";
    }
} catch (Throwable $e) {
    echo "Gagal membuat admin: {$e->getMessage()}\n";
    $fail++;
}

// ── Laporan akhir ──
echo "\n=== Selesai ===\n";
echo ($fail === 0)
    ? "Semua langkah berhasil.\n"
    : "{$fail} langkah bermasalah (lihat pesan di atas).\n";

// Bukan CLI → tutup dengan tampilan sederhana
if (PHP_SAPI !== 'cli') {
    echo "\n<a href=\"../index.php\">← Kembali ke beranda</a>";
}

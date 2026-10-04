<?php
// Bootstrap koneksi database. Kredensial ada di config.local.php (gitignored).
//
// CATATAN timezone: kolom timestamp di database menyimpan waktu LOKAL (WIB, +07),
// dan PHP menampilkannya apa adanya lewat strtotime(). Karena itu sesi Postgres
// harus memakai Asia/Jakarta - kalau di-UTC, setiap baris baru akan tersimpan
// 7 jam di belakang baris lama.

require_once __DIR__ . '/config.local.php';
require_once __DIR__ . '/includes/Db.php';

if (!extension_loaded('pdo_pgsql')) {
    http_response_code(500);
    exit('Aktifkan extension=pdo_pgsql di php.ini, lalu restart Apache.');
}
if ($db_host === 'ISI_HOST_SESSION_POOLER' || $db_user === 'postgres.ISI_PROJECT_REF'
    || $db_password === 'ISI_PASSWORD_DATABASE') {
    http_response_code(500);
    exit('Isi parameter koneksi Supabase di config.local.php terlebih dahulu.');
}

$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$database_name};sslmode=require;connect_timeout=10";

try {
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET search_path TO public");
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");   // bukan 'UTC' - lihat catatan di atas
    $pdo->exec("SET client_encoding TO 'UTF8'");
    $db = new Db($pdo);
} catch (PDOException $e) {
    error_log('Supabase connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi Supabase gagal. Cek parameter koneksi dan log error Apache.');
}
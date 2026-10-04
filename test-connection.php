<?php
// Temporary local connection check. Remove this file after testing.
require __DIR__ . '/database.php';
header('Content-Type: text/plain; charset=UTF-8');
try {
    $count = $db->query('SELECT COUNT(*) FROM public.listings')->fetchColumn();
    echo "Koneksi Supabase berhasil!\n";
    echo 'Jumlah listing: ' . (int)$count . "\n";
} catch (PDOException $e) {
    error_log('Supabase table check failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Koneksi berhasil, tetapi tabel public.listings belum bisa dibaca. Cek impor tabel dan hak akses.';
}
<?php
// Smoke test SESSIONS. Jalankan: php tests/smoke.php
// Exit 0 = semua lulus, exit 1 = ada yang gagal.

$root = dirname(__DIR__);
$GLOBALS['__fails'] = 0;
$GLOBALS['__pass']  = 0;

function ok(string $name, bool $cond, string $extra = ''): void {
    if ($cond) { $GLOBALS['__pass']++; echo "  PASS  $name\n"; }
    else       { $GLOBALS['__fails']++; echo "  FAIL  $name $extra\n"; }
}
function section(string $t): void { echo "\n== $t ==\n"; }

// -- 1. Koneksi ----------------------------------------------------
require $root . '/database.php';
section('Koneksi');
// CATATAN: build PHP ini case-SENSITIVE untuk lookup method, jadi pakai nama
// persis seperti yang dideklarasikan (snake_case) dan seperti yang dipakai app.
ok('tabel users terbaca',    (int)$db->query('SELECT COUNT(*) FROM users')->fetch_column()    >= 0);
ok('tabel listings terbaca', (int)$db->query('SELECT COUNT(*) FROM listings')->fetch_column() >= 0);

// -- 2. Shim: prepare + bind_param + get_result -------------------
section('Shim DbStatement');
$st = $db->prepare('SELECT id, title FROM listings WHERE status = ? AND price > ? ORDER BY id');
$status = 'available'; $min = 1000;
$st->bind_param('sd', $status, $min);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
ok('bind_param + fetch_all', is_array($rows) && count($rows) > 0, '(got ' . count($rows) . ' rows)');
ok('fetch_all kolom tepat', isset($rows[0]['id'], $rows[0]['title']));
$st->close();
ok('close() tidak fatal', true);

section('Shim Db (query + fetch_row)');
$res = $db->query('SELECT COUNT(*) FROM users');
$row = $res ? $res->fetch_row() : null;
ok('query()->fetch_row()', is_array($row) && (int)$row[0] > 0);

section('Shim DbResult');
$res2 = $db->query('SELECT id FROM users ORDER BY id LIMIT 2');
$a = $res2->fetch_assoc();
ok('num_rows', $res2->num_rows === 2, '(got ' . $res2->num_rows . ')');
ok('fetch_assoc', is_array($a) && isset($a['id']));

section('Shim query() non-SELECT harus truthy');
$okUpd = $db->query("UPDATE orders SET status = status WHERE id = 1");
ok('UPDATE no-op tetap truthy', $okUpd === true, '(got ' . var_export($okUpd, true) . ')');

section('Shim insert_id via RETURNING');
$db->beginTransaction();
$ins = $db->prepare('INSERT INTO reports (reporter_id, listing_id, reason) VALUES (?, ?, ?)');
$r = 26; $l = 1; $why = '__smoke__';
$ins->bind_param('iis', $r, $l, $why);
$ins->execute();
$newId = (int)$ins->get_result()->fetch_row()[0];
ok('insert_id numeric > 0', $newId > 0, '(got ' . $newId . ')');
$db->rollBack();
ok('rollBack tidak meninggalkan baris',
   (int)$db->query("SELECT COUNT(*) FROM reports WHERE reason = '__smoke__'")->fetch_row()[0] === 0);

// -- 2b. Boolean binding (regresi: PHP false ditolak Postgres) ---------
section('Boolean binding');
$db->beginTransaction();
try {
    foreach ([true, false] as $flag) {
        $st = $db->prepare('INSERT INTO listing_images (listing_id, image_url, is_primary)
                            SELECT id, ?, ? FROM listings LIMIT 1');
        $url = 'ZZ_smoke_' . ($flag ? 'T' : 'F') . '.png';
        $st->bind_param('sb', $url, $flag);
        $st->execute();
        $row = $db->queryParams(
            'SELECT is_primary, pg_typeof(is_primary) AS t FROM listing_images WHERE id = ?',
            [$st->insert_id]
        )->fetch_assoc();
        ok('bind ' . var_export($flag, true) . ' -> boolean',
            $row && $row['is_primary'] === $flag && $row['t'] === 'boolean',
            '(' . ($row['t'] ?? '?') . ')');
    }
    $n = $db->query('SELECT COUNT(*) AS c FROM listing_images WHERE is_primary = true')->fetch_column();
    ok('filter is_primary = true jalan', (int)$n >= 0, "({$n} baris)");
} catch (Throwable $e) {
    ok('boolean binding', false, '(' . substr($e->getMessage(), 0, 60) . ')');
} finally {
    $db->rollBack();
}
$left = $db->query("SELECT COUNT(*) AS c FROM listing_images WHERE image_url LIKE 'ZZ_smoke%'")->fetch_column();
ok('tidak ada sisa baris boolean', (int)$left === 0);

// -- 3. SQL portability --------------------------------------------
section('SQL portability');
$cases = [
    'FIELD() diganti CASE'     => "SELECT id FROM orders ORDER BY CASE status WHEN 'menunggu_bukti' THEN 0 WHEN 'diverifikasi' THEN 1 WHEN 'proses' THEN 2 WHEN 'selesai' THEN 3 WHEN 'batal' THEN 4 ELSE 5 END, created_at DESC LIMIT 1",
    'is_primary boolean'       => 'SELECT id FROM listing_images WHERE is_primary = true LIMIT 1',
    'settings tanpa backtick'  => 'SELECT key, value FROM settings LIMIT 1',
    'condition tanpa backtick' => 'SELECT condition FROM listings LIMIT 1',
    'single-quoted literal'    => "SELECT 1 AS x FROM orders WHERE status = 'batal' LIMIT 1",
];
foreach ($cases as $name => $sql) {
    try { $db->query($sql); ok($name, true); }
    catch (Throwable $e) { ok($name, false, '-> ' . substr($e->getMessage(), 0, 90)); }
}

// -- 4. Tidak ada sisa mysqli --------------------------------------
section('Tidak ada sisa mysqli di halaman');
$bad = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $p = $f->getPathname();
    if (substr($p, -4) !== '.php') continue;
    if (strpos($p, '\\tests\\') !== false || strpos($p, '\\.git\\') !== false) continue;
    if (basename($p) === 'migrate.php') continue; // punya wrapper sendiri
    if (basename($p) === 'Db.php') continue;       // shim itu sendiri hanya menyebut mysqli di komentar
    if (preg_match('/\bmysqli\b/', file_get_contents($p))) $bad[] = basename($p);
}
ok('tidak ada "mysqli" di file halaman', count($bad) === 0, '(' . implode(', ', $bad) . ')');

echo "\n=== LULUS: {$GLOBALS['__pass']} | GAGAL: {$GLOBALS['__fails']} ===\n";
exit($GLOBALS['__fails'] === 0 ? 0 : 1);
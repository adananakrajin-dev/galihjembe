# Plan: Port SESSIONS dari MySQL/mysqli ke PostgreSQL (Supabase)

Tanggal: 2026-10-01 | Workspace: `C:\laragon\www\bisnis`

---

## Goal

Mengubah aplikasi SESSIONS agar berjalan penuh di Supabase PostgreSQL: aktifkan
`pdo_pgsql`, pasang shim kompatibilitas mysqli di atas PDO, dan perbaiki semua SQL
yang tidak portabel — tanpa mengubah fitur atau perilaku yang sudah ada.

---

## Koreksi penting: database SUDAH ada di Supabase

**Tidak ada yang perlu di-upload.** Data dan skema sudah ada dan identik dengan MySQL
lokal. Sudah saya verifikasi langsung ke server (read-only):

| Tabel | MySQL `ukk_login` | Supabase `public` |
|---|---|---|
| users | 26 | 26 |
| listings | 6 | 6 |
| orders | 7 | 7 |
| reviews | 3 | 3 |
| briefs | 1 | 1 |
| categories | 10 | 10 |
| listing_subtypes | 19 | 19 |
| listing_brands | 35 | 35 |
| listing_images | 5 | 5 |
| seller_profiles | 2 | 2 |
| settings | 8 | 8 |

Bukti kesamaan: daftar username urut dari kedua DB identik
(`Xscleton,adananakrajinnn@gmail.com,2222,Ahdan,loeus,...`), dan listing id 1-12 punya
judul, harga, status, serta `created_at` yang sama persis.

Skema juga **sudah Postgres asli**:

- 14 tabel di schema `public`
- 13 tabel punya `id ... GENERATED ... AS IDENTITY` (bukan `AUTO_INCREMENT`)
- 4 trigger `*_set_updated_at` terpasang: `briefs`, `listings`, `orders`, `seller_profiles`
- Server `PostgreSQL 17.11`
- `listing_images.is_primary` bertipe **`boolean`** (di MySQL asli: `TINYINT`)

Artinya pekerjaan yang tersisa adalah **migrasi kode**, bukan migrasi data.

---

## Current context / assumptions

### Yang sudah working

`database.php` (gitignored) **sudah** memakai PDO `pgsql` dan sudah saya tes berhasil:
14 tabel terbaca, prepared statement jalan, `BEGIN`/`ROLLBACK` jalan, `backend_pid`
stabil (= session pooler, jadi transaksi aman).

Kredensial pooler: host `aws-0-ap-northeast-1.pooler.supabase.com`, port `5432`,
dbname `postgres`, user `postgres.<project-ref>`, `sslmode=require`.

### Blocker

**Aplikasi 100% mati sekarang.** Semua halaman balas HTTP 500 karena
`;extension=pdo_pgsql` masih dikomentari di
`C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini` baris 819.

### Masalah struktural

`database.php` **tidak di-track git** (ada di `.gitignore`), padahal 24 file
meng-`include` file itu. Fresh clone jadi fatal. Plan ini ikut memperbaikinya.

### Catatan data

- **15 user punya `email` NULL/kosong, dan ada 2 duplikat email.** Unique index
  `uq_users_email` (ada di `schema.sql` MySQL) **tidak bisa** dibuat di Postgres tanpa
  pembersihan data. Index itu memang tidak ada di DB live — biarkan begitu.
- `users.email` di DB live adalah `NOT NULL`. Jangan diubah.

### Timezone - jebakan yang harus ditangani

`database.php` sekarang melakukan `SET TIME ZONE 'UTC'`. Semua baris lama tersimpan
sebagai **waktu lokal (WIB, +07)** - sudah saya bandingkan nilainya dengan MySQL dan
sama persis. Default timezone PHP = `UTC`, dan kode menampilkan lewat
`date('d M Y H:i', strtotime($created_at))`, sehingga angka wall-clock tersimpan
ditampilkan apa adanya.

Konsekuensi: kalau `SET TIME ZONE 'UTC'` dibiarkan, setiap baris baru akan tersimpan
**7 jam di belakang** baris lama. Harus diubah ke `Asia/Jakarta`.

### Breker SQL - sudah saya konfirmasi GAGAL di server

| Pola | Jumlah | Error nyata dari Postgres |
|---|---|---|
| `FIELD(col, "a","b")` | 8 | `function field(unknown, unknown) does not exist` |
| `img.is_primary = 1` | 6 | `operator does not exist: boolean = integer` |
| literal kutip-ganda `status = "batal"` | 22 | `column "batal" does not exist` |
| backtick `` `key` `` / `` `condition` `` | 3 | `syntax error at or near "`"` |
| typehint `mysqli $db` | 5 | `TypeError`: PDO bukan mysqli |
| `$stmt->insert_id` | 2 | method tidak ada |
| `SHOW COLUMNS` / `INSERT IGNORE` / `ALTER ... AFTER` | `migrate.php` | `syntax error` |

### Jebakan lain yang sudah saya uji

1. **`rowCount()` = 0 untuk no-op UPDATE.** `seller-orders.php:20-28` memakai
   `$ok = $db->query("UPDATE ...")` lalu `if ($ok)`. mysqli selalu `true`; PDO
   `rowCount()` mengembalikan 0 saat tidak ada baris berubah. Shim **wajib**
   mengembalikan `true` untuk non-SELECT.
2. **`PDO::lastInsertId()` tidak bisa dipakai.** Terbukti melempar
   `SQLSTATE[55000]: lastval is not yet defined in this session`. Perlu `RETURNING id`
   yang di-append otomatis.
3. **Error di dalam transaksi membatalkan seluruh transaksi.** Shim perlu
   `SAVEPOINT` agar satu query gagal tidak menewaskan seluruh request.

---

## Architecture / proposed approach

Satu shim kompatibilitas: file tracked `includes/Db.php` berisi kelas `Db`,
`DbStatement`, `DbResult` yang meniru API mysqli di atas PDO. Dengan begitu 24 file
halaman hampir tidak berubah - hanya 5 typehint `mysqli` dan SQL non-portabel yang
perlu disentuh. Pemisahan kredensial: `config.local.php` (tetap gitignored) menyimpan
sandera, `database.php` (dijadikan **tracked**) hanya bootstrap yang memanggil shim.

---

## Step-by-step tasks

### Task 1 - Aktifkan `pdo_pgsql` (blocker, kerjakan pertama)

`pdo_pgsql` di-comment sehingga semua halaman 500.

```bash
cd C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64
sed -i 's/^;extension=pdo_pgsql$/extension=pdo_pgsql/' php.ini
grep -n '^extension=pdo_pgsql' php.ini
```

Expected output: `819:extension=pdo_pgsql`

Verifikasi CLI (tidak perlu restart Apache):

```bash
php -m | grep -E 'pdo_pgsql'
```

Expected: `pdo_pgsql`

Lalu **restart Apache** lewat Laragon (klik kanan icon Laragon -> Apache -> Stop/Start),
atau dari CLI:

```bash
taskkill /F /IM httpd.exe 2>/dev/null; sleep 2; C:/laragon/bin/apache/*/bin/httpd.exe -k start
```

Tidak ada commit (di luar repo). Tidak ada test - ini perubahan environment.

---

### Task 2 - Tulis test smoke (gagal dulu)

Repo tidak punya test framework (tidak ada `composer.json`/`phpunit.xml`). Buat skrip
PHP polos yang dipakai sepanjang plan ini.

Buat `tests/smoke.php`:

```php
<?php
// Smoke test SESSIONS. Jalankan: php tests/smoke.php
// Exit 0 = semua lulus, exit 1 = ada yang gagal.

$root = dirname(__DIR__);
$GLOBALS['__fails'] = 0;
$GLOBALS['__pass']  = 0;

function ok(string $name, bool $cond, string $extra = ''): void {
    if ($cond) { $GLOBALS['__pass']++; echo "  PASS  $name\n"; }
    else       { $GLOBALS['__fail']++; echo "  FAIL  $name $extra\n"; }
}
function section(string $t): void { echo "\n== $t ==\n"; }

// -- 1. Koneksi ----------------------------------------------------
require $root . '/database.php';
section('Koneksi');
ok('tabel users terbaca',    (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()    >= 0);
ok('tabel listings terbaca', (int)$db->query('SELECT COUNT(*) FROM listings')->fetchColumn() >= 0);

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
    if (preg_match('/\bmysqli\b/', file_get_contents($p))) $bad[] = basename(dirname($p)) . '/' . basename($p);
}
ok('tidak ada "mysqli" di file halaman', count($bad) === 0, '(' . implode(', ', $bad) . ')');

echo "\n=== LULUS: {$GLOBALS['__pass']} | GAGAL: {$GLOBALS['__fail']} ===\n";
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
```

Jalankan - harus GAGAL (shim belum ada):

```bash
cd C:/laragon/www/bisnis && php tests/smoke.php
```

Expected: exit 1, banyak `FAIL` (mysqli masih ada, `is_primary = true` belum di-fix).

Commit:

```bash
git add tests/smoke.php && git commit -m "test: smoke test harness untuk port Postgres (belum lulus)"
```

---

### Task 3 - Buat shim `includes/Db.php`

Buat `includes/Db.php` - inti dari seluruh plan. Ini menggantikan file `includes/Db.php`
yang belum ada.

```php
<?php
// includes/Db.php - shim kompatibilitas mysqli di atas PDO PostgreSQL.
//
// Meniru API mysqli yang dipakai seluruh aplikasi:
//   $db->prepare() / $db->query()
//   $stmt->bind_param('si', ...$args)   (args by-reference, seperti mysqli)
//   $stmt->execute() / $stmt->get_result() / $stmt->close() / $stmt->insert_id
//   $result->fetch_assoc() / fetch_row() / fetch_all(MYSQLI_ASSOC) / $result->num_rows
//
// Dua hal yang TIDAK bisa diandalkan di Postgres:
//   - PDO::lastInsertId() melempar "lastval is not yet defined in this session",
//     jadi INSERT otomatis diberi "RETURNING id".
//   - rowCount() mengembalikan 0 untuk no-op UPDATE, padahal mysqli::query() selalu
//     true. Shim mengembalikan true untuk non-SELECT.

if (!defined('MYSQLI_ASSOC')) { define('MYSQLI_ASSOC', 1); }

class DbResult
{
    private array $rows;
    private int $cursor = 0;
    public int $num_rows;

    public function __construct(array $rows)
    {
        $this->rows     = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc(): ?array { return $this->rows[$this->cursor++] ?? null; }

    public function fetch_row(): ?array
    {
        $r = $this->fetch_assoc();
        return $r === null ? null : array_values($r);
    }

    /** mysqli: fetch_all(int $mode = MYSQLI_ASSOC). Mode NUM -> array nilai. */
    public function fetch_all(int $mode = MYSQLI_ASSOC): array
    {
        $out = [];
        foreach ($this->rows as $r) {
            $out[] = ($mode === MYSQLI_NUM) ? array_values($r) : $r;
        }
        $this->cursor = count($this->rows);
        return $out;
    }
}

class DbStatement
{
    private PDOStatement $stmt;
    private string $sql;
    private array  $params = [];
    private bool   $has_returning = false;

    public int $insert_id = 0;

    public function __construct(PDO $pdo, string $sql)
    {
        // INSERT wajib dapat RETURNING id supaya insert_id terisi.
        if (!preg_match('/\breturning\b/i', $sql) && preg_match('/^\s*INSERT\b/i', $sql)) {
            $sql = rtrim(rtrim($sql), ';') . ' RETURNING id';
            $this->has_returning = true;
        }
        $this->sql  = $sql;
        $this->stmt = $pdo->prepare($sql);
    }

    /** mysqli: bind_param(string $types, mixed &...$vars). Nilai dibiarkan tipe aslinya. */
    public function bind_param(string $types, &...$vars): bool
    {
        $this->params = array_values($vars);
        return true;
    }

    public function execute(): bool
    {
        $ok = $this->stmt->execute($this->params);
        if ($ok && $this->has_returning) {
            $row = $this->stmt->fetch(PDO::FETCH_NUM);
            if ($row) { $this->insert_id = (int)$row[0]; }
        }
        return $ok;
    }

    /** mysqli: $stmt->get_result()->fetch_*. Buffering, boleh dipanggil ulang. */
    public function get_result(): DbResult
    {
        return new DbResult($this->stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function fetch_assoc(): ?array { return $this->get_result()->fetch_assoc(); }
    public function fetch_row(): ?array    { return $this->get_result()->fetch_row(); }
    public function num_rows(): int       { return $this->get_result()->num_rows; }

    /** mysqli: tidak ada padanan; PDO tidak butuh. */
    public function close(): bool { return true; }
}

class Db
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function prepare(string $sql): DbStatement
    {
        return new DbStatement($this->pdo, $sql);
    }

    /**
     * mysqli::query() mengembalikan mysqli_result untuk SELECT dan bool true untuk
     * UPDATE/INSERT/DELETE - bahkan ketika 0 baris terpengaruh. Shim meniru itu.
     */
    public function query(string $sql)
    {
        $returnsRows = (bool)preg_match('/^\s*(SELECT|WITH|SHOW|PRAGMA|EXPLAIN)\b/i', $sql);
        $stmt = $this->pdo->query($sql);
        if ($returnsRows) {
            return new DbResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        return true;
    }

    public function pdo(): PDO { return $this->pdo; }

    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool           { return $this->pdo->commit(); }
    public function rollBack(): bool          { return $this->pdo->rollBack(); }
    public function inTransaction(): bool    { return $this->pdo->inTransaction(); }
    public function lastInsertId(): string   { return (string)$this->pdo->lastInsertId(); }
}
```

Verifikasi sintaks:

```bash
cd C:/laragon/www/bisnis && php -l includes/Db.php
```

Expected: `No syntax errors detected in includes/Db.php`

Commit:

```bash
git add includes/Db.php && git commit -m "feat(db): shim kompatibilitas mysqli di atas PDO PostgreSQL"
```

---

### Task 4 - Ubah `database.php` jadi tracked bootstrap + timezone

Pindahkan kredensial ke `config.local.php` (tetap gitignored) dan hapus `database.php`
dari `.gitignore` supaya ikut ter-track.

`database.php` - **ganti seluruh isi** dengan:

```php
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
```

`config.local.php` - **ganti isi** (tetap gitignored, tidak di-commit):

```php
<?php
// Kredensial Supabase (RAHASIA - file ini di-ignore oleh git).
// Ambil dari Supabase Dashboard > Project Settings > Database > Session pooler.

$db_host       = 'ISI_HOST_SESSION_POOLER';
$db_port       = '5432';
$database_name = 'postgres';
$db_user       = 'postgres.ISI_PROJECT_REF';
$db_password   = 'ISI_PASSWORD_DATABASE';
```

Lalu hapus baris `database.php` dari `.gitignore`:

```bash
cd C:/laragon/www/bisnis && sed -i '/^database\.php$/d' .gitignore && grep -c 'database.php' .gitignore
```

Expected: `0`

Verifikasi:

```bash
cd C:/laragon/www/bisnis && php tests/smoke.php 2>&1 | head -20
```

Expected: bagian "Koneksi" PASS (shim sudah ada di Task 3, timezone sudah benar).

Commit:

```bash
git add database.php .gitignore
git commit -m "refactor(db): pisahkan kredensial ke config.local.php, track bootstrap database.php"
```

> `config.local.php` **jangan** di-`git add` - sudah di-ignore.

---

### Task 5 - Ganti 5 typehint `mysqli`

`mysqli` sudah tidak dipakai; menggantinya dengan `Db` supaya tidak fatal TypeError.

Di `includes/functions.php`:

- baris 143: `function db_all(mysqli $db, string $sql, string $types = '', ...$args): array {`
  -> `function db_all(Db $db, string $sql, string $types = '', ...$args): array {`
- baris 151: `function db_one(mysqli $db, string $sql, string $types = '', ...$args): ?array {`
  -> `function db_one(Db $db, string $sql, string $types = '', ...$args): ?array {`
- baris 78: `if (!isset($_SESSION['user_id']) || !($db instanceof mysqli)) { return; }`
  -> `if (!isset($_SESSION['user_id']) || !($db instanceof Db)) { return; }`

Di `dashboard.php` baris 11 dan `admin/index.php` baris 6:

- `function try_count(mysqli $db, string $sql): int`
  -> `function try_count(Db $db, string $sql): int`

 pervasive: `dashboard.php` juga memanggil `$res->fetch_row()` pada hasil `$db->query()`
(sudah didukung shim).

Verifikasi:

```bash
cd C:/laragon/www/bisnis && grep -rn 'mysqli' --include=*.php . | grep -v migrate.php
```

Expected: tidak ada output.

Commit:

```bash
git add -A && git commit -m "refactor: ganti typehint mysqli dengan Db di helper dan try_count"
```

---

### Task 6 - Ganti `FIELD()` dengan `CASE` (8 tempat)

`FIELD()` tidak ada di Postgres. Gunakan `CASE ... END` (sudah saya tes berhasil,
urutan sama persis dengan `FIELD`).

Pola umum:
```sql
-- SEBELUM (MySQL)
ORDER BY FIELD(o.status, "menunggu_bukti", "diverifikasi", "proses", "selesai", "batal"), o.created_at DESC

-- SESUDAH (Postgres)
ORDER BY CASE o.status WHEN 'menunggu_bukti' THEN 0 WHEN 'diverifikasi' THEN 1 WHEN 'proses' THEN 2 WHEN 'selesai' THEN 3 WHEN 'batal' THEN 4 ELSE 5 END, o.created_at DESC
```

Terapkan per file:

**`admin/listings.php:39`**
```php
$items = db_all($db, $sql . ' ORDER BY FIELD(l.moderation, "pending", "approved", "rejected"), l.created_at DESC');
```
->
```php
$items = db_all($db, $sql . " ORDER BY CASE l.moderation WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END, l.created_at DESC");
```

**`admin/orders.php:56`**
```php
ORDER BY FIELD(o.status, "menunggu_bukti", "diverifikasi", "proses", "selesai", "batal"), o.created_at DESC'
```
->
```php
ORDER BY CASE o.status WHEN \'menunggu_bukti\' THEN 0 WHEN \'diverifikasi\' THEN 1 WHEN \'proses\' THEN 2 WHEN \'selesai\' THEN 3 WHEN \'batal\' THEN 4 ELSE 5 END, o.created_at DESC\'
```

**`admin/reports.php:48`**
```php
ORDER BY FIELD(r.status, "pending", "resolved", "rejected"), r.created_at DESC'
```
->
```php
ORDER BY CASE r.status WHEN \'pending\' THEN 0 WHEN \'resolved\' THEN 1 WHEN \'rejected\' THEN 2 ELSE 3 END, r.created_at DESC\'
```

**`admin/sellers.php:51`**
```php
ORDER BY FIELD(sp.approval, "pending", "approved", "rejected"), sp.updated_at DESC'
```
->
```php
ORDER BY CASE sp.approval WHEN \'pending\' THEN 0 WHEN \'approved\' THEN 1 WHEN \'rejected\' THEN 2 ELSE 3 END, sp.updated_at DESC\'
```

**`admin/users.php:62` dan `:71`** (dua tempat, identik)
```php
ORDER BY FIELD(u.role, "admin", "seller", "buyer"), u.created_at DESC',
```
->
```php
ORDER BY CASE u.role WHEN \'admin\' THEN 0 WHEN \'seller\' THEN 1 WHEN \'buyer\' THEN 2 ELSE 3 END, u.created_at DESC\',
```

**`seller-orders.php:83`**
```php
ORDER BY FIELD(o.status, "menunggu_bukti", "diverifikasi", "proses", "selesai", "batal"), o.created_at DESC',
```
-> sama seperti `admin/orders.php:56` di atas.

**`seller-orders.php:97`**
```php
ORDER BY FIELD(b.status, "pending", "quoted"), b.created_at DESC',
```
->
```php
ORDER BY CASE b.status WHEN \'pending\' THEN 0 WHEN \'quoted\' THEN 1 ELSE 2 END, b.created_at DESC\',
```

Verifikasi - harus 0:

```bash
cd C:/laragon/www/bisnis && grep -rn 'FIELD(' --include=*.php . | wc -l
```

Expected: `0`

Commit:

```bash
git add -A && git commit -m "fix(sql): ganti MySQL FIELD() dengan CASE untuk Postgres"
```

---

### Task 7 - Ganti `is_primary = 1` jadi `= true` (6 tempat)

Kolom `listing_images.is_primary` bertipe `boolean` di Postgres. `= 1` gagal dengan
`operator does not exist: boolean = integer`. (Bind parameter integer sudah aman -
sudah saya tes - hanya literal SQL yang bermasalah.)

Enam file, pola identik `AND img.is_primary = 1`:

| File | Baris |
|---|---|
| `admin/listings.php` | 34 |
| `favorites.php` | 37 |
| `index.php` | 25 |
| `index.php` | 41 |
| `listings.php` | 100 |
| `my-listings.php` | 59 |

Ganti di semua:

```sql
AND img.is_primary = 1
```
->
```sql
AND img.is_primary = true
```

Verifikasi - harus 0:

```bash
cd C:/laragon/www/bisnis && grep -rn 'is_primary = 1' --include=*.php . | wc -l
```

Expected: `0`

Commit:

```bash
git add -A && git commit -m "fix(sql): is_primary boolean true, bukan integer 1"
```

---

### Task 8 - Ganti literal kutip-ganda dan backtick di SQL

#### 8a. Backtick (3 tempat)

**`checkout.php:41`**
```php
foreach (db_all($db, 'SELECT `key`, `value` FROM settings') as $s) {
```
->
```php
foreach (db_all($db, 'SELECT key, value FROM settings') as $s) {
```
(`key` dan `value` bukan reserved word di Postgres - sudah saya tes berhasil.)

**`listing-form.php:198`** (query UPDATE)
```php
                        `condition`=?, location=?, website=?, stack=?, moderation=?,
```
->
```php
                        condition=?, location=?, website=?, stack=?, moderation=?,
```

**`listing-form.php:216`** (query INSERT)
```php
                                       `condition`, location, website, stack, moderation,
```
->
```php
                                       condition, location, website, stack, moderation,
```
(`condition` juga bukan reserved word - sudah saya tes `SELECT condition FROM listings` berhasil.)

#### 8b. Kutip-ganda literal (22 tempat, semuanya di string SQL)

Daftar lengkapberdasarkan grep (file:baris):

| File | Baris | Cuplikan |
|---|---|---|
| `index.php` | 26 | `WHERE l.moderation = "approved" AND l.status <> "inactive" AND l.type = "service"` |
| `index.php` | 42 | idem, `... = "product"` |
| `admin/orders.php` | 19 | `UPDATE orders SET status = "diverifikasi" WHERE id = ?` |
| `admin/orders.php` | 34 | `UPDATE orders SET status = "batal" WHERE id = ? AND status <> "selesai"` |
| `admin/sellers.php` | 26 | `UPDATE users SET role = ? WHERE id = ? AND role <> "admin"` |
| `become-seller.php` | 81 | `VALUES (?, ?, ?, ?, ?, "pending")'` |
| `brief.php` | 54 | `UPDATE briefs SET status = "rejected" ... status = "quoted"` |
| `brief.php` | 72 | `WHERE u.id = ? AND (sp.approval = "approved" OR sp.user_id IS NULL)` |
| `listing-form.php` | 218 | `..., "pending", ?, ?, ?, ?, ?)'` |
| `my-listings.php` | 56 | `... AND o.status <> "batal") AS order_count` |
| `order-create.php` | 137 | `VALUES (?, ?, ..., "menunggu_bukti")'` |
| `order-create.php` | 151 | `UPDATE briefs SET status = "accepted" WHERE id = ?` |
| `register.php` | 38 | `CASE WHEN email = ? THEN "email" ELSE "username" END AS jenis` |
| `register.php` | 51 | `VALUES (?, ?, ?, ?, ?, "buyer", "active")'` |
| `seller-orders.php` | 56 | `... AND status = "pending" AND (seller_id IS NULL ...` |
| `seller-orders.php` | 65 | `UPDATE briefs SET ..., status = "quoted" WHERE id = ?` |
| `seller-orders.php` | 96 | `AND b.status <> "accepted"` |
| 8 sisanya | - | ikut terhapus oleh Task 6 (baris `FIELD(...)`) |

Aturan: di dalam string SQL, ganti `"` menjadi `'` **hanya untuk literal nilai**.
Perhatikan konteks string PHP: kalau string SQL itu diapit `'...'`, isi kutip-ganda
harus jadi `\'...\'` agar tidak menutup string PHP.

Contoh `order-create.php:137` (string diapit `'`):
```php
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "menunggu_bukti")'
```
->
```php
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'menunggu_bukti\')'
```

Contoh `register.php:38` (string diapit `'`):
```php
                    CASE WHEN email = ? THEN "email" ELSE "username" END AS jenis
```
->
```php
                    CASE WHEN email = ? THEN \'email\' ELSE \'username\' END AS jenis
```

Contoh `brief.php:72` (string diapit `'`):
```php
             WHERE u.id = ? AND (sp.approval = "approved" OR sp.user_id IS NULL)',
```
->
```php
             WHERE u.id = ? AND (sp.approval = \'approved\' OR sp.user_id IS NULL)\',
```

Verifikasi - harus 0 (pola SQL dengan literal kutip-ganda):

```bash
cd C:/laragon/www/bisnis && grep -rnE "(WHERE|AND|SET|VALUES|<>) *\(?\"[a-z_]+\"" --include=*.php . | grep -v '<?php' | wc -l
```

Expected: `0`

Commit:

```bash
git add -A && git commit -m "fix(sql): literal kutip-ganda ke kutip-satu, hapus backtick"
```

---

### Task 9 - Tulis ulang `database/schema.sql` ke dialek Postgres

`schema.sql` sekarang MySQL (`AUTO_INCREMENT`, `ENUM`, `ENGINE=InnoDB`, `INSERT IGNORE`,
`ON UPDATE CURRENT_TIMESTAMP`, backtick). Ganti seluruh isinya agar bisa dipakai
`CREATE TABLE IF NOT EXISTS` di Postgres dan menghasilkan skema yang **sama persis**
dengan yang sudah ada di Supabase.

```sql
-- ============================================================
-- SESSIONS - Marketplace Jasa Web & Produk
-- Skema database (PostgreSQL / Supabase)
--
-- Cara pakai:
--   1. CLI : php database/migrate.php
--   2. Manual : tempel di Supabase Dashboard > SQL Editor > New query > Run
-- ============================================================

-- ── Pengguna ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    name        varchar(100) NOT NULL DEFAULT '',
    email       varchar(150) NOT NULL,
    username    varchar(20)  NOT NULL,
    password    varchar(255) NOT NULL,      -- hash password_hash(), bukan plaintext
    phone       varchar(25)  NULL,
    location    varchar(100) NULL,
    avatar      varchar(255) NULL,
    role        text NOT NULL DEFAULT 'buyer',    -- buyer | seller | admin
    status      text NOT NULL DEFAULT 'active',   -- active | inactive
    created_at  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  timestamp NULL DEFAULT NULL,
    CONSTRAINT uq_users_username UNIQUE (username)
);
-- CATATAN: tidak ada UNIQUE pada email. Di DB live ada 15 user tanpa email dan
-- 2 email duplikat, jadi unique index tidak bisa dibuat tanpa pembersihan data.
-- Pengecekan duplikasi email dilakukan di level aplikasi (register.php, become-seller.php).

-- ── Profil toko (multi-seller, menunggu approval admin) ──────
CREATE TABLE IF NOT EXISTS seller_profiles (
    id           integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    user_id      integer NOT NULL,
    store_name   varchar(100) NOT NULL,
    store_desc   text NULL,
    payout_info  varchar(100) NULL,          -- rekening / e-wallet pencairan
    home_address text NULL,                 -- alamat rumah (HANYA untuk admin)
    approval     text NOT NULL DEFAULT 'pending',  -- pending | approved | rejected
    created_at   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_seller_user UNIQUE (user_id),
    CONSTRAINT fk_seller_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- updated_at dipelihara trigger, bukan ON UPDATE (tidak ada di Postgres).
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS seller_profiles_set_updated_at ON seller_profiles;
CREATE TRIGGER seller_profiles_set_updated_at BEFORE UPDATE ON seller_profiles
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ── Kategori ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id         integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    name       varchar(60) NOT NULL,
    slug       varchar(60) NOT NULL,
    type       text NOT NULL DEFAULT 'all',   -- product | service | all
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_cat_slug UNIQUE (slug)
);

-- ── Subtipe listing per kategori ──────────────────────────────
CREATE TABLE IF NOT EXISTS listing_subtypes (
    id          integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    category_id integer NOT NULL,
    name        varchar(60) NOT NULL,
    slug        varchar(60) NOT NULL,
    created_at  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_subtype_slug UNIQUE (category_id, slug),
    CONSTRAINT fk_subtype_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ── Merek per subtipe ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS listing_brands (
    id         integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    subtype_id integer NOT NULL,
    name       varchar(60) NOT NULL,
    slug       varchar(60) NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_brand_slug UNIQUE (subtype_id, slug),
    CONSTRAINT fk_brand_subtype FOREIGN KEY (subtype_id) REFERENCES listing_subtypes(id) ON DELETE CASCADE
);

-- ── Listing (produk / jasa) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS listings (
    id           integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    seller_id    integer NOT NULL,
    category_id  integer NULL,
    subtype_id   integer NULL,
    brand_id     integer NULL,
    type         text NOT NULL DEFAULT 'product',    -- product | service
    title        varchar(60)  NOT NULL,
    description  text NOT NULL,
    price        numeric(15,2) NOT NULL DEFAULT 0.00,
    condition    text NULL,
    location     varchar(100) NULL,
    province     varchar(100) NULL,
    regency      varchar(100) NULL,
    district     varchar(100) NULL,
    rt           varchar(10)  NULL,
    rw           varchar(10)  NULL,
    website      varchar(255) NULL,
    stack        varchar(255) NULL,
    status       text NOT NULL DEFAULT 'available',  -- available | sold | inactive
    moderation   text NOT NULL DEFAULT 'pending',    -- pending | approved | rejected
    created_at   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_listing_seller   FOREIGN KEY (seller_id)   REFERENCES users(id)            ON DELETE CASCADE,
    CONSTRAINT fk_listing_category FOREIGN KEY (category_id) REFERENCES categories(id)       ON DELETE SET NULL,
    CONSTRAINT fk_listing_subtype  FOREIGN KEY (subtype_id)  REFERENCES listing_subtypes(id)  ON DELETE SET NULL,
    CONSTRAINT fk_listing_brand    FOREIGN KEY (brand_id)    REFERENCES listing_brands(id)    ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_listing_subtype ON listings(subtype_id);
CREATE INDEX IF NOT EXISTS idx_listing_brand   ON listings(brand_id);

DROP TRIGGER IF EXISTS listings_set_updated_at ON listings;
CREATE TRIGGER listings_set_updated_at BEFORE UPDATE ON listings
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ── Paket harga (khusus jasa) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS listing_packages (
    id             integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    listing_id     integer NOT NULL,
    name           varchar(60) NOT NULL,
    price          numeric(15,2) NOT NULL,
    duration_days  integer NULL,
    revisions      integer NULL,
    features       text NULL,
    sort_order     smallint NOT NULL DEFAULT 0,
    CONSTRAINT fk_package_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- ── Foto listing ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS listing_images (
    id         integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    listing_id integer NOT NULL,
    image_url  varchar(255) NOT NULL,
    is_primary boolean NOT NULL DEFAULT false,   -- boolean, bukan TINYINT
    CONSTRAINT fk_image_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- ── Brief kebutuhan (jasa custom) ─────────────────────────────
CREATE TABLE IF NOT EXISTS briefs (
    id            integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    buyer_id      integer NOT NULL,
    seller_id     integer NULL,
    title         varchar(150) NOT NULL,
    requirements  text NOT NULL,
    budget_min    numeric(15,2) NULL,
    budget_max    numeric(15,2) NULL,
    deadline      date NULL,
    quote_price   numeric(15,2) NULL,
    status        text NOT NULL DEFAULT 'pending',  -- pending | quoted | accepted | rejected
    created_at    timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_brief_buyer  FOREIGN KEY (buyer_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_brief_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
);

DROP TRIGGER IF EXISTS briefs_set_updated_at ON briefs;
CREATE TRIGGER briefs_set_updated_at BEFORE UPDATE ON briefs
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ── Pesanan (bayar manual: bayar -> upload bukti -> verifikasi) ──
CREATE TABLE IF NOT EXISTS orders (
    id               integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    order_code       varchar(20) NOT NULL,
    buyer_id         integer NOT NULL,
    seller_id        integer NOT NULL,
    listing_id       integer NULL,
    package_id       integer NULL,
    brief_id         integer NULL,
    title            varchar(150) NOT NULL,
    total            numeric(15,2) NOT NULL,
    payment_method   varchar(20) NULL,
    payment_proof    varchar(255) NULL,
    shipping_address text NULL,
    status           text NOT NULL DEFAULT 'menunggu_bukti',
                     -- menunggu_bukti | diverifikasi | proses | selesai | batal
    notes            text NULL,
    created_at       timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_order_code UNIQUE (order_code),
    CONSTRAINT fk_order_buyer   FOREIGN KEY (buyer_id)   REFERENCES users(id)            ON DELETE CASCADE,
    CONSTRAINT fk_order_seller  FOREIGN KEY (seller_id)  REFERENCES users(id)            ON DELETE CASCADE,
    CONSTRAINT fk_order_listing FOREIGN KEY (listing_id) REFERENCES listings(id)        ON DELETE SET NULL,
    CONSTRAINT fk_order_package FOREIGN KEY (package_id) REFERENCES listing_packages(id) ON DELETE SET NULL,
    CONSTRAINT fk_order_brief   FOREIGN KEY (brief_id)   REFERENCES briefs(id)          ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_order_buyer  ON orders(buyer_id);
CREATE INDEX IF NOT EXISTS idx_order_seller ON orders(seller_id);
CREATE INDEX IF NOT EXISTS idx_order_status ON orders(status);

DROP TRIGGER IF EXISTS orders_set_updated_at ON orders;
CREATE TRIGGER orders_set_updated_at BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ── Ulasan (1 order = 1 ulasan, setelah selesai) ──────────────
CREATE TABLE IF NOT EXISTS reviews (
    id               integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    order_id         integer NOT NULL,
    buyer_id         integer NOT NULL,
    seller_id        integer NOT NULL,
    listing_id       integer NULL,
    rating           smallint NOT NULL,      -- 1..5
    comment          text NULL,
    created_at       timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       timestamp NULL DEFAULT NULL,
    reply            text NULL,
    replied_at       timestamp NULL,
    reply_updated_at timestamp NULL,
    CONSTRAINT uq_review_order UNIQUE (order_id),
    CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ── Favorit ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS favorites (
    id         integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    user_id    integer NOT NULL,
    listing_id integer NOT NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_fav UNIQUE (user_id, listing_id),
    CONSTRAINT fk_fav_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_fav_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- ── Laporan listing & ulasan ──────────────────────────────────
CREATE TABLE IF NOT EXISTS reports (
    id          integer GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
    reporter_id integer NOT NULL,
    listing_id  integer NULL,      -- NULL bila laporan menyorot ulasan saja
    review_id   integer NULL,
    reason      varchar(255) NOT NULL,
    status      text NOT NULL DEFAULT 'pending',   -- pending | resolved | rejected
    created_at  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_report_review UNIQUE (reporter_id, review_id),
    CONSTRAINT fk_report_user    FOREIGN KEY (reporter_id) REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_report_listing FOREIGN KEY (listing_id)  REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_review  FOREIGN KEY (review_id)   REFERENCES reviews(id)  ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_report_status ON reports(status);
CREATE INDEX IF NOT EXISTS idx_report_review ON reports(review_id);

-- ── Pengaturan situs ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    key   varchar(60) PRIMARY KEY,
    value text NOT NULL
);

-- ── Seed kategori ─────────────────────────────────────────────
INSERT INTO categories (name, slug, type) VALUES
    ('Jasa Web',              'jasa-web',              'service'),
    ('Elektronik',            'elektronik',            'product'),
    ('Fashion',               'fashion',               'product'),
    ('Buku',                  'buku',                  'product'),
    ('Furnitur',              'furnitur',              'product'),
    ('Peralatan Rumah Tangga','peralatan-rumah-tangga','product'),
    ('Hobi',                  'hobi',                  'product'),
    ('Kendaraan',             'kendaraan',             'product'),
    ('Aksesoris',             'aksesoris',             'product'),
    ('Lainnya',               'lainnya',               'all')
ON CONFLICT (slug) DO NOTHING;

-- ── Seed pengaturan pembayaran ────────────────────────────────
INSERT INTO settings (key, value) VALUES
    ('payment_wa',           'ISI_NOMOR_WA'),
    ('payment_qris',         'assets/img/qr.jpeg'),
    ('payment_ewallet_num',  'ISI_NOMOR_EWALLET'),
    ('payment_ewallet_name', 'SESSIONS STUDIO'),
    ('payment_bca',          'ISI_REKENING_BCA'),
    ('payment_bni',          'ISI_REKENING_BNI'),
    ('payment_bri',          'ISI_REKENING_BRI'),
    ('payment_bank_name',    'ISI_NAMA_BANK')
ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value;
```

> **PENTING - jangan jalankan `schema.sql` ke DB yang sudah ada.** Saat ini DB live
> sudah benar. `CREATE TABLE IF NOT EXISTS` aman (tidak menimpa), dan seed pakai
> `ON CONFLICT` - tapi `payment_wa` dsb akan di-overwrite dengan placeholder `ISI_*`
> jika Anda menjalankannya. Ganti dulu placeholder itu dengan nilai asli, atau
> skip bagian seed bila data settings sudah benar.

Commit:

```bash
git add database/schema.sql && git commit -m "refactor(schema): schema.sql ke dialek PostgreSQL"
```

---

### Task 10 - Tulis ulang `database/migrate.php`

`migrate.php` memakai `SHOW COLUMNS`, `INSERT IGNORE`, `ALTER ... AFTER`, `ENUM`, dan
`$stmt->get_result()` - semuanya tidak ada di Postgres. Karena DB live sudah lengkap,
fungsi migrate berubah jadi: **verifikasi + seed idempotent**, bukan menambal kolom
satu per satu.

Ganti seluruh isi `database/migrate.php`:

```php
<?php
/**
 * Migrasi database SESSIONS (PostgreSQL / Supabase).
 *
 * Pakai:
 *   - CLI   : php database/migrate.php
 *   - Lokal : buka http://localhost/bisnis/database/migrate.php (hanya dari localhost)
 *
 * Yang dilakukan:
 *   1. Menjalankan seluruh statement di database/schema.sql
 *      (CREATE TABLE IF NOT EXISTS + trigger + seed, semuanya idempotent)
 *   2. Seed subtipe & merek per kategori (ON CONFLICT DO NOTHING)
 *   3. Membuat akun admin default bila belum ada (admin / admin123)
 *
 * Catatan: tidak ada lagi "penambalan kolom" ala MySQL. Postgres tidak punya
 * ALTER TABLE ... AFTER, jadi kolom baru harus ditangani lewat schema.sql.
 */

// -- Penjaga: hanya CLI atau akses lokal --
if (PHP_SAPI !== 'cli') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        die('Hanya bisa dijalankan dari localhost atau CLI.');
    }
}

require __DIR__ . '/../database.php';

echo "=== SESSIONS DB Migrasi (PostgreSQL) ===\n";
echo "Database: {$database_name}\n\n";

$ok = 0; $fail = 0; $skip = 0;

// -- 1. Jalankan schema.sql, statement per statement --
$sql = file_get_contents(__DIR__ . '/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Gagal membaca schema.sql\n");
    exit(1);
}

// Buang komentar baris (-- ...), lalu pecah per pernyataan ';'.
// Dollar-quoted body trigger ($$ ... $$) sudah ditulis tanpa titik koma di dalamnya.
$lines = array_filter(explode("\n", $sql), fn($l) => strpos(ltrim($l), '--') !== 0);
$statements = explode(';', implode("\n", $lines));

foreach ($statements as $i => $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') { $skip++; continue; }
    try {
        $db->query($stmt);
        $ok++;
    } catch (Throwable $e) {
        $fail++;
        echo "[SQL #" . ($i + 1) . "] GAGAL: " . substr($e->getMessage(), 0, 160) . "\n";
        echo "         " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 120) . "...\n";
    }
}
echo "Schema   : {$ok} pernyataan OK, {$fail} gagal, {$skip} kosong\n";

// -- 2. Seed subtipe & merek per kategori --
$subtype_seed = [
    'kendaraan'  => ['Sepeda', 'Motor', 'Mobil', 'Bajaj', 'Pesawat', 'Kapal'],
    'elektronik' => ['Handphone', 'Laptop', 'Kamera', 'Televisi', 'Audio'],
    'buku'       => ['Novel', 'Komik', 'Buku Pelajaran', 'Majalah'],
    'furnitur'   => ['Meja', 'Kursi', 'Lemari', 'Kasur'],
];
$brand_seed = [
    'kendaraan|Sepeda'     => ['Polygon', 'United', 'Wim Cycle', 'Federal'],
    'kendaraan|Motor'      => ['Honda', 'Yamaha', 'Suzuki', 'Kawasaki'],
    'kendaraan|Mobil'      => ['Toyota', 'Daihatsu', 'Mitsubishi', 'Honda', 'Suzuki', 'Hyundai'],
    'elektronik|Handphone' => ['Samsung', 'Apple', 'Xiaomi', 'Oppo', 'Vivo'],
    'elektronik|Laptop'    => ['Asus', 'Acer', 'Lenovo', 'Apple', 'HP'],
    'elektronik|Kamera'    => ['Canon', 'Nikon', 'Sony', 'Fujifilm'],
    'elektronik|Televisi'  => ['Samsung', 'LG', 'Sony', 'TCL'],
    'elektronik|Audio'     => ['Sony', 'JBL', 'Sennheiser'],
];

$attr_slug = function (string $s): string {
    $s = strtolower(trim($s));
    $s = str_replace([' ', '/'], ['-', '-'], $s);
    return preg_replace('/[^a-z0-9\-]/', '', $s);
};

$seeded_sub = 0; $seeded_brand = 0;
try {
    foreach ($subtype_seed as $cat_slug => $names) {
        $row = db_one($db, 'SELECT id FROM categories WHERE slug = ?', 's', $cat_slug);
        if (!$row) { continue; }

        foreach ($names as $name) {
            $db->query(
                'INSERT INTO listing_subtypes (category_id, name, slug) VALUES (?, ?, ?)
                 ON CONFLICT (category_id, slug) DO NOTHING',
                [$row['id'], $name, $attr_slug($name)]
            );
        }
    }

    foreach ($brand_seed as $key => $names) {
        [$cat_slug, $sub_name] = explode('|', $key, 2);
        $sub = db_one(
            $db,
            'SELECT s.id FROM listing_subtypes s
             JOIN categories c ON c.id = s.category_id
             WHERE c.slug = ? AND s.name = ?',
            'ss', $cat_slug, $sub_name
        );
        if (!$sub) { continue; }

        foreach ($names as $name) {
            $db->query(
                'INSERT INTO listing_brands (subtype_id, name, slug) VALUES (?, ?, ?)
                 ON CONFLICT (subtype_id, slug) DO NOTHING',
                [$sub['id'], $name, $attr_slug($name)]
            );
        }
    }

    $sub_n = (int)db_one($db, 'SELECT COUNT(*) AS c FROM listing_subtypes')['c'];
    $br_n  = (int)db_one($db, 'SELECT COUNT(*) AS c FROM listing_brands')['c'];
    echo "atribut: {$sub_n} subtipe, {$br_n} merek (seed idempotent)\n";
} catch (Throwable $e) {
    echo "atribut: gagal seed - {$e->getMessage()}\n";
    $fail++;
}

// -- 3. Akun admin default --
try {
    $has_admin = db_one($db, "SELECT id FROM users WHERE role = 'admin' LIMIT 1", 's', 'admin');
    if (!$has_admin) {
        $db->query(
            "INSERT INTO users (name, email, username, password, role, status)
             VALUES ('Administrator', 'admin@sessions.local', 'admin', ?, 'admin', 'active')",
            [password_hash('admin123', PASSWORD_DEFAULT)]
        );
        echo "\nAkun admin dibuat -> username: admin | password: admin123 (GANTI setelah masuk!)\n";
    } else {
        echo "Akun admin sudah ada.\n";
    }
} catch (Throwable $e) {
    echo "Gagal membuat admin: {$e->getMessage()}\n";
    $fail++;
}

// -- Laporan akhir --
echo "\n=== Selesai ===\n";
echo ($fail === 0) ? "Semua langkah berhasil.\n" : "{$fail} langkah bermasalah (lihat pesan di atas).\n";

if (PHP_SAPI !== 'cli') {
    echo "\n<a href=\"../index.php\">&#8592; Kembali ke beranda</a>";
}
```

Verifikasi:

```bash
cd C:/laragon/www/bisnis && php -l database/migrate.php
```

Expected: `No syntax errors detected in database/migrate.php`

Lalu jalankan (idempotent - aman diulang):

```bash
cd C:/laragon/www/bisnis && php database/migrate.php
```

Expected: `Schema   : N pernyataan OK, 0 gagal, M kosong` dan
`atribut: 19 subtipe, 35 merek (seed idempotent)` dan `Akun admin sudah ada.`

Commit:

```bash
git add database/migrate.php && git commit -m "refactor(migrate): migrasi database ke PostgreSQL, idempotent via ON CONFLICT"
```

---

### Task 11 - Bersihkan file probe & dokumentasi

#### 11a. Hapus `test-connection.php`

File ini untracked, ada di webroot, dan membocorkan jumlah listing lewat HTTP.

```bash
cd C:/laragon/www/bisnis && rm -f test-connection.php && git status --short
```

Expected: `git status --short` tidak menampilkan `?? test-connection.php`.

#### 11b. Rotasi password database (WAJIB - keamanan)

Password Supabase tersimpan plaintext di `config.local.php` dan sempat tampil di output
terminal. **Rotasi di Supabase Dashboard > Project Settings > Database**, lalu update
`config.local.php`.

#### 11c. Update README

Di `README.md`, ganti bagian MySQL (sekitar baris 83-119: "Buat MySQL database
`ukk_login`", `mysqli_connect`, `root`/password kosong) dengan penjelasan Supabase.
Ganti juga `README.md:3` ("PHP native + MySQL") dan `docs/BRD.md:10`, `docs/PRD.md:9`,
`docs/SRS.md` (beberapa baris) menjadi PostgreSQL.

Commit:

```bash
git add README.md docs/ && git commit -m "docs: dokumentasi setup Supabase PostgreSQL"
```

---

## Tests / validation

### Siklus TDD per task

Plan ini mengikuti RED -> GREEN -> COMMIT. Urutannya:

| Task | RED (test gagal) | GREEN (test lulus) | Commit |
|---|---|---|---|
| 2 | `php tests/smoke.php` -> exit 1, banyak FAIL | (belum lulus - ini langkah RED) | ya |
| 3 | masih FAIL | `php -l includes/Db.php` ->_no syntax errors_ | ya |
| 4 | masih FAIL | bagian "Koneksi" PASS | ya |
| 5 | FAIL "mysqli" masih terdeteksi | `grep -rn mysqli` -> kosong | ya |
| 6 | - | `grep -rn 'FIELD(' \| wc -l` -> `0` | ya |
| 7 | - | `grep -rn 'is_primary = 1' \| wc -l` -> `0` | ya |
| 8 | - | grep literal kutip-ganda -> `0` | ya |
| 9 | - | `php -l database/schema.sql` via migrate | ya |
| 10 | - | `php database/migrate.php` -> `0 gagal` | ya |
| 11 | - | `php tests/smoke.php` -> **exit 0, semua PASS** | ya |

### Verifikasi akhir (WAJIB semua hijau)

```bash
cd C:/laragon/www/bisnis

# 1. Smoke test - harus exit 0
php tests/smoke.php; echo "exit=$?"

# 2. Sintaks semua file PHP
for f in $(find . -name '*.php' -not -path './.git/*'); do php -l "$f" | grep -v 'No syntax errors'; done; echo "lint bersih"

# 3. Tidak ada sisa mysqli / FIELD() / is_primary = 1
grep -rn 'mysqli' --include=*.php . | grep -v migrate.php | wc -l   # harus 0
grep -rn 'FIELD(' --include=*.php . | wc -l                          # harus 0
grep -rn 'is_primary = 1' --include=*.php . | wc -l                   # harus 0

# 4. Migrasi idempoten (jalankan 2x, hasil harus sama)
php database/migrate.php && php database/migrate.php
```

### Smoke test manual lewat browser

Setelah Task 1 & 4, buka `http://localhost/bisnis/index.php` dan cek **tidak ada
HTTP 500**. Lalu login `admin` / `admin123` dan telusuri:

| Halaman | Yang harus dicek |
|---|---|
| `/index.php` | 4 kartu featured + 8 chip kategori muncul |
| `/listings.php` | grid listing, filter kategori/subtype/merek jalan |
| `/listings.php?type=service` | badge "Jasa" |
| `/listing-detail.php?id=1` | foto utama tampil (join `is_primary = true`) |
| `/login.php` | login berhasil, masuk dashboard |
| `/dashboard.php` | 4 angka statistik (pakai `try_count`) |
| `/admin/index.php` | kartu statistik + urutan role admin/seller/buyer |
| `/admin/orders.php` | urutan status pesanan benar (pending dulu) |
| `/admin/sellers.php` | urutan approval pending dulu |
| `/admin/users.php` | urutan admin/seller/buyer |
| `/admin/reports.php` | urutan status laporan |
| `/orders.php` | daftar pesanan pembeli |
| `/seller-orders.php` | daftar pesanan + brief (login sbg seller) |
| `/profile.php` | ubah profil tersimpan |
| `/checkout.php?order=<id>` | upload bukti bayar |

**Cek timezone secara eksplisit.** Ini yang paling mudah lolos dari smoke test tapi
paling merusak. Setelah login dan melihat satu pesanan yang sudah ada (dibuat 29 Sep),
catat jam yang tampil. Lalu buat entri baru (mis. upload avatar / edit profil yang
memicu `updated_at`) dan cek jamnya **konsisten** - tidak melompat 7 jam ke belakang.

Verifikasi SQL pembanding:

```bash
php -r 'require "database.php"; var_dump($db->query("SELECT now()")->fetch_row()[0]);'
```

Nilai ini harus **jam lokal WIB** (~14:xx), bukan UTC (~07:xx).

---

## Risks, tradeoffs, and open questions

### Risiko

1. **Timezone salah = data bohong.** Kalau Task 4 lupa, setiap baris baru tersimpan 7
   jam mundur._geographically paling sulit dideteksi karena tidak error, hanya salah
   tampil. Sudah ada check eksplisit di atas.
2. **Password DB sudah terekspos** (tampil di output terminal + plaintext di
   `config.local.php`). Rotasi **wajib** - Task 11b.
3. **`schema.sql` bisa merusak data settings.** Seed memakai
   `ON CONFLICT DO UPDATE`, jadi menjalankannya akan overwrite `payment_wa` dsb dengan
   placeholder `ISI_*`. Sudah diberi warning di Task 9, tapi implementer harus skip
   bagian seed atau ganti placeholder dulu.
4. **Session pooler + prepared statement.** Sudah saya tes berulang (3x prepare
   berturut-turut) aman, dan `backend_pid` stabil. Tapi kalau nanti port-nya diganti ke
   transaction pooler (port 6543), prepared statement akan gagal. Jangan ganti port.
5. **Tidak ada test coverage jalur tulis yang lengkap.** Smoke test hanya menutup
   `INSERT reports`. Form listing (`listing-form.php`) punya 3 INSERT berantai
   (listings -> listing_images -> listing_packages) yang belum terotomatisasi -
   hanya tercover smoke test manual di browser.
6. **`migrate.php` memecah SQL dengan `explode(';')`.** Aman selama tidak ada `;` di
   dalam string literal atau body trigger. Body trigger di `schema.sql` sengaja
   ditulis tanpa `;` di dalamnya - jangan diubah tanpa verifying ulang.

### Tradeoff yang diambil

- **Shim, bukan rewrite penuh ke PDO native.** 24 file jadi tidak berubah struktur,
  risiko regresi rendah. Konsekuensinya: ada lapisan abstraksi yang bukan idiomatik Postgres
  (dualisme `query()` return `bool|Result`). Kalau nanti sudah stabil, `Db` bisa
  dibongkar bertahap - tapi itu pekerjaan terpisah, bukan sekarang (YAGNI).
- **Kolom `role`/`status` tetap `text`, bukan ENUM.** Ini konsisten dengan DB live
  (sudah `text`). ENUM Postgres lebih ketat tapi butuh migrasi data + rewrite
  constraint. Tidak sepadan sekarang.
- **`SET TIME ZONE 'Asia/Jakarta'` di level sesi**, bukan `UTC` +
  konversi di PHP. Konsisten dengan data yang sudah ada dan dengan kode tampilan
  yang sekarang. Solusi "benar" adalah `timestamptz`, tapi itu mengubah semua data
  lama - tidak perlu untuk sekarang.
- **Tidak menambah `pg_dump`.** Tidak tersedia di mesin ini. Verifikasi dilakukan
  via query PHP langsung, hasilnya sama untuk keperluan validasi.

### Open questions (perlu keputusan Anda)

1. **Refresh data dari MySQL lokal?** Data sudah identik, jadi **tidak perlu**. Tapi
   kalau Anda melakukan dump ulang MySQL dengan isi terbaru, sebutkan - nanti saya
   tambahkan skrip export/import (Task 12).
2. **Settings placeholder di `schema.sql`.** Seed-nya sayaisi `ISI_NOMOR_WA` dll.
   Mau saya isi dengan data asli yang sudah ada di DB (6287867851779,
   0878-6785-1779, dst.), atau dibiarkan placeholder supaya Anda isi sendiri?
3. **`docs/SRS.md` (956 baris) - extent of rewrite.** Cuma ganti kata "MySQL" ->
   "PostgreSQL" (cepat), atau adjust diagram arsitektur &amp; spesifikasi technical
   yang berubah (lebih lama)?

### Di luar scope

- Migrasi ke Supabase Auth (login masih `password_hash()` + session PHP).
- Supabase Storage (upload masih ke folder `uploads/` lokal).
- Row Level Security (tidak ada; aplikasi pakai user `postgres` penuh).
- Environment production / deploy (semua ini lokal Laragon + Supabase project Anda).

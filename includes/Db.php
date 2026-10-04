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
if (!defined('MYSQLI_NUM'))  { define('MYSQLI_NUM', 2);  }

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

    /** mysqli: fetch_column() -> nilai kolom pertama dari baris berikutnya. */
    public function fetch_column(int $column = 0)
    {
        $row = $this->fetch_row();
        return $row === null ? null : ($row[$column] ?? null);
    }
}

class DbStatement
{
    private PDOStatement $stmt;
    private string $sql;
    private array  $params = [];
    private bool   $has_returning = false;
    private ?array $returning_row = null;

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
        $this->returning_row = null;
        $this->insert_id = 0;

        if ($ok && $this->has_returning) {
            // Buffer baris RETURNING supaya get_result() tetap bisa membacanya
            // (kalau langsung di-fetch di sini, kursor result sudah habis).
            $this->returning_row = $this->stmt->fetch(PDO::FETCH_ASSOC);
            if ($this->returning_row && isset($this->returning_row['id'])) {
                $this->insert_id = (int)$this->returning_row['id'];
            }
        }
        return $ok;
    }

    /** mysqli: $stmt->get_result()->fetch_*. Buffering, boleh dipanggil ulang. */
    public function get_result(): DbResult
    {
        if ($this->returning_row !== null) {
            return new DbResult([$this->returning_row]);
        }
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

    /** Query berparameter untuk Db::query() (dipakai migrate.php). */
    public function queryParams(string $sql, array $params = [])
    {
        $returnsRows = (bool)preg_match('/^\s*(SELECT|WITH|SHOW|PRAGMA|EXPLAIN)\b/i', $sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($returnsRows) {
            return new DbResult($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        return $stmt->rowCount();
    }

    public function pdo(): PDO { return $this->pdo; }

    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool           { return $this->pdo->commit(); }
    public function rollBack(): bool          { return $this->pdo->rollBack(); }
    public function inTransaction(): bool    { return $this->pdo->inTransaction(); }
    public function lastInsertId(): string   { return (string)$this->pdo->lastInsertId(); }
}
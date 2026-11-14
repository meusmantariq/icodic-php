<?php
declare(strict_types=1);
namespace App\Core;

use PDO;
use PDOException;

/**
 * Idempotent SQL migration runner.
 *
 * - Every file in database/migrations/*.sql is applied exactly once, in filename order.
 * - Applied versions are recorded in `schema_migrations` together with a sha256 checksum
 *   and an incrementing batch number, so the same file is never executed twice.
 * - Existing rows and tables are never dropped or rewritten: migrations must be written
 *   with `CREATE TABLE IF NOT EXISTS` / `ADD COLUMN IF NOT EXISTS` style DDL.
 * - A file whose content changed after being applied is reported as MODIFIED and skipped
 *   (non-fatal) so an unrelated deploy cannot silently re-run altered SQL.
 *
 * MySQL/MariaDB DDL is auto-committing, therefore each statement is applied on its own and
 * the version row is inserted only after every statement of that file succeeded. Because the
 * shipped DDL is idempotent, a partially applied file is safe to re-run.
 */
final class Migrator
{
    private const TABLE = 'schema_migrations';

    /** @var callable|null */
    private $log;

    public function __construct(?callable $log = null) { $this->log = $log; }

    public static function directory(): string { return BASE_PATH . '/database/migrations'; }

    /** Create the bookkeeping table when missing (used on fresh installs too). */
    public static function ensureTable(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
            version VARCHAR(120) NOT NULL,
            checksum CHAR(64) NOT NULL,
            batch INT UNSIGNED NOT NULL DEFAULT 1,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    /** @return array<string,array{version:string,path:string,checksum:string}> */
    public static function files(): array
    {
        $dir = self::directory();
        $out = [];
        foreach (glob($dir . '/*.sql') ?: [] as $path) {
            $version = basename($path, '.sql');
            $out[$version] = ['version' => $version, 'path' => $path, 'checksum' => hash_file('sha256', $path) ?: ''];
        }
        ksort($out, SORT_STRING);
        return $out;
    }

    /** @return array<string,array{checksum:string,batch:int,applied_at:string}> */
    public function applied(PDO $pdo): array
    {
        self::ensureTable($pdo);
        $rows = [];
        foreach ($pdo->query('SELECT version, checksum, batch, applied_at FROM ' . self::TABLE) as $r) $rows[$r['version']] = $r;
        return $rows;
    }

    /**
     * @return array<int,array{version:string,state:string,batch:?int,applied_at:?string}>
     *         state: applied | pending | modified
     */
    public function status(?PDO $pdo = null): array
    {
        $pdo = $pdo ?? Database::pdo();
        $applied = $this->applied($pdo);
        $out = [];
        foreach (self::files() as $version => $f) {
            $a = $applied[$version] ?? null;
            if ($a === null) $state = 'pending';
            elseif ($a['checksum'] !== $f['checksum']) $state = 'modified';
            else $state = 'applied';
            $out[] = ['version' => $version, 'state' => $state, 'batch' => $a ? (int)$a['batch'] : null, 'applied_at' => $a['applied_at'] ?? null];
        }
        return $out;
    }

    /**
     * Apply all pending migrations.
     *
     * @return array{applied:string[],skipped:string[],modified:string[],batch:int}
     */
    public function migrate(?PDO $pdo = null): array
    {
        $pdo = $pdo ?? Database::pdo();
        $applied = $this->applied($pdo);
        $batch = 1;
        foreach ($applied as $a) $batch = max($batch, (int)$a['batch'] + 1);

        $result = ['applied' => [], 'skipped' => [], 'modified' => [], 'batch' => $batch];
        foreach (self::files() as $version => $file) {
            if (isset($applied[$version])) {
                if ($applied[$version]['checksum'] !== $file['checksum']) { $result['modified'][] = $version; $this->say("  MODIFIED  $version (already applied — not re-run)"); }
                else { $result['skipped'][] = $version; }
                continue;
            }
            $this->say("  applying  $version");
            $this->applyFile($pdo, $file['path']);
            $stmt = $pdo->prepare('INSERT INTO ' . self::TABLE . ' (version, checksum, batch) VALUES (?, ?, ?)');
            $stmt->execute([$version, $file['checksum'], $batch]);
            $result['applied'][] = $version;
        }
        return $result;
    }

    /** Execute every statement of one migration file. */
    private function applyFile(PDO $pdo, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) throw new \RuntimeException("Cannot read migration: $path");
        $statements = self::split($sql);
        if (!$statements) throw new \RuntimeException("Migration has no statements: $path");
        foreach ($statements as $i => $statement) {
            try { $pdo->exec($statement); }
            catch (PDOException $e) {
                throw new \RuntimeException(sprintf('Migration %s failed at statement %d: %s | SQL: %s',
                    basename($path), $i + 1, $e->getMessage(), mb_substr(preg_replace('/\s+/', ' ', $statement) ?? '', 0, 300)), 0, $e);
            }
        }
    }

    /**
     * Split a SQL script into statements, honouring single/double/backtick quotes,
     * line comments (`--`, `#`) and block comments. Semicolons inside literals and
     * comments therefore never split a statement — the naive `preg_split('/;\s*\n/')`
     * used by the old installer would corrupt such files.
     *
     * @return string[]
     */
    public static function split(string $sql): array
    {
        $statements = [];
        $buf = '';
        $len = strlen($sql);
        $inS = $inD = $inB = false;   // quote state
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';
            if (!$inS && !$inD && !$inB) {
                if ($ch === '-' && $next === '-') { while ($i < $len && $sql[$i] !== "\n") $i++; $buf .= "\n"; continue; }
                if ($ch === '#') { while ($i < $len && $sql[$i] !== "\n") $i++; $buf .= "\n"; continue; }
                if ($ch === '/' && $next === '*') { $i += 2; while ($i < $len && !($sql[$i] === '*' && ($sql[$i + 1] ?? '') === '/')) $i++; $i++; $buf .= ' '; continue; }
                if ($ch === ';') { $t = trim($buf); if ($t !== '') $statements[] = $t; $buf = ''; continue; }
            }
            if ($ch === "'" && !$inD && !$inB) {
                // handle backslash escapes and doubled quotes
                if ($inS && $next === "'") { $buf .= "''"; $i++; continue; }
                if (!$inS && $i > 0 && $sql[$i - 1] === '\\') { $buf .= $ch; continue; }
                $inS = !$inS; $buf .= $ch; continue;
            }
            if ($ch === '"' && !$inS && !$inB) { $inD = !$inD; $buf .= $ch; continue; }
            if ($ch === '`' && !$inS && !$inD) { $inB = !$inB; $buf .= $ch; continue; }
            $buf .= $ch;
        }
        $t = trim($buf);
        if ($t !== '') $statements[] = $t;
        return $statements;
    }

    private function say(string $line): void { if ($this->log) ($this->log)($line); }
}

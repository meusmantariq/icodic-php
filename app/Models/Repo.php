<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;

/** Generic, schema-driven repository used by both the public site and the admin CRUD. */
final class Repo
{
    private static ?array $settings = null;

    public static function settings(): array
    {
        if (self::$settings !== null) return self::$settings;
        try { self::$settings = DB::fetch('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1') ?? []; } catch (\Throwable) { self::$settings = []; }
        return self::$settings;
    }

    public static function all(string $table, string $orderBy = 'id DESC', int $limit = 500, array $where = []): array
    {
        self::guard($table);
        [$sql, $params] = self::whereSql($where);
        return DB::fetchAll("SELECT * FROM `$table` $sql ORDER BY $orderBy LIMIT " . (int)$limit, $params);
    }
    public static function find(string $table, int $id): ?array { self::guard($table); return DB::fetch("SELECT * FROM `$table` WHERE id = ?", [$id]); }
    public static function findBy(string $table, string $col, mixed $val): ?array { self::guard($table); self::guard($col); return DB::fetch("SELECT * FROM `$table` WHERE `$col` = ? LIMIT 1", [$val]); }
    public static function count(string $table, array $where = []): int { self::guard($table); [$sql, $p] = self::whereSql($where); return (int)DB::scalar("SELECT COUNT(*) FROM `$table` $sql", $p); }

    public static function insert(string $table, array $data): int
    {
        self::guard($table);
        $cols = array_keys($data); array_walk($cols, fn($c) => self::guard($c));
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ')';
        DB::query($sql, array_values($data));
        return DB::lastId();
    }
    public static function update(string $table, int $id, array $data): void
    {
        self::guard($table);
        if (!$data) return;
        $set = [];
        foreach (array_keys($data) as $c) { self::guard($c); $set[] = "`$c` = ?"; }
        DB::query("UPDATE `$table` SET " . implode(', ', $set) . ' WHERE id = ?', [...array_values($data), $id]);
    }
    public static function delete(string $table, int $id): void { self::guard($table); DB::query("DELETE FROM `$table` WHERE id = ?", [$id]); }

    private static function guard(string $ident): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $ident)) throw new \InvalidArgumentException("Bad identifier: $ident");
    }
    private static function whereSql(array $where): array
    {
        if (!$where) return ['', []];
        $parts = []; $params = [];
        foreach ($where as $col => $val) { self::guard($col); if ($val === null) { $parts[] = "`$col` IS NULL"; } else { $parts[] = "`$col` = ?"; $params[] = $val; } }
        return ['WHERE ' . implode(' AND ', $parts), $params];
    }
}

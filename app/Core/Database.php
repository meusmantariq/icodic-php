<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) return self::$pdo;
        $db = Config::get('db');
        $dsn = !empty($db['socket'])
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $db['socket'], $db['name'], $db['charset'] ?? 'utf8mb4')
            : sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], (int)($db['port'] ?? 3306), $db['name'], $db['charset'] ?? 'utf8mb4');
        try {
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            self::$pdo->exec("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Check app/config/config.php.');
        }
        return self::$pdo;
    }

    public static function connect(array $db): PDO
    {
        $dsn = !empty($db['socket'])
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $db['socket'], $db['name'])
            : sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], (int)($db['port'] ?? 3306), $db['name']);
        return new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array { return self::query($sql, $params)->fetchAll(); }
    public static function fetch(string $sql, array $params = []): ?array { $r = self::query($sql, $params)->fetch(); return $r === false ? null : $r; }
    public static function scalar(string $sql, array $params = []): mixed { return self::query($sql, $params)->fetchColumn(); }
    public static function lastId(): int { return (int)self::pdo()->lastInsertId(); }
}

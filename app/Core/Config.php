<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $data = [];

    public static function load(array $data): void { self::$data = $data; }

    /** Dot-notation getter: Config::get('db.host', 'localhost') */
    public static function get(string $key, mixed $default = null): mixed
    {
        $node = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) return $default;
            $node = $node[$part];
        }
        return $node;
    }

    public static function all(): array { return self::$data; }
}

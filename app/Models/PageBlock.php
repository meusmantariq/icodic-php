<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database as DB;

/** Page-builder block lookup: returns defaults merged with the visible DB block for page+section. */
final class PageBlock
{
    private static array $cache = [];

    public static function get(string $page, string $section, array $defaults = []): array
    {
        if (!isset(self::$cache[$page])) {
            try { $rows = DB::fetchAll('SELECT * FROM page_blocks WHERE page = ? AND visible = 1 ORDER BY sort_order ASC', [$page]); } catch (\Throwable) { $rows = []; }
            self::$cache[$page] = [];
            foreach ($rows as $r) self::$cache[$page][$r['section']] ??= $r;
        }
        $b = self::$cache[$page][$section] ?? null;
        $data = ['title' => $defaults['title'] ?? null, 'subtitle' => $defaults['subtitle'] ?? null, 'body' => $defaults['body'] ?? null, 'image_url' => $defaults['image_url'] ?? null, 'type' => $defaults['type'] ?? 'text'];
        if ($b) foreach (['title', 'subtitle', 'body', 'image_url'] as $k) { if ($b[$k] !== null && $b[$k] !== '') $data[$k] = $b[$k]; }
        if ($b && $b['type']) $data['type'] = $b['type'];
        return $data;
    }
}

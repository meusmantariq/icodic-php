<?php
declare(strict_types=1);
namespace App\Core;

final class View
{
    private static array $shared = [];
    public static function share(string $key, mixed $value): void { self::$shared[$key] = $value; }
    public static function render(string $view, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) throw new \RuntimeException("View not found: $view");
        extract(self::$shared, EXTR_SKIP); extract($data, EXTR_OVERWRITE);
        ob_start();
        try { require $file; } catch (\Throwable $e) { ob_end_clean(); throw $e; }
        return ob_get_clean();
    }
    public static function page(string $view, array $data = [], string $layout = 'layouts.main'): string
    {
        $content = self::render($view, $data);
        return self::render($layout, $data + ['content' => $content]);
    }
}

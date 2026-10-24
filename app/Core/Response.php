<?php
declare(strict_types=1);
namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
    }
    public static function redirect(string $to, int $status = 302): never { header('Location: ' . url($to), true, $status); exit; }
    public static function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? ''; $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($ref && $host && parse_url($ref, PHP_URL_HOST) === $host) { header('Location: ' . $ref); exit; }
        self::redirect($fallback);
    }
    public static function notFound(string $message = 'The page you are looking for does not exist.'): never
    {
        http_response_code(404);
        echo View::page('errors.404', ['title' => 'Page not found', 'message' => $message]); exit;
    }
}

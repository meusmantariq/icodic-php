<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function check(Request $req): void
    {
        $token = $req->input('_token') ?? $req->header('X-CSRF-Token');
        if (!self::verify($token)) {
            if ($req->wantsJson()) Response::json(['ok' => false, 'error' => 'Invalid or expired form token. Please reload the page.'], 419);
            http_response_code(419);
            echo View::render('errors/419', ['title' => 'Session expired']);
            exit;
        }
    }
}

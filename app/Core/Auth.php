<?php
declare(strict_types=1);
namespace App\Core;

final class Auth
{
    private static ?array $user = null;

    public static function check(): bool { return self::user() !== null; }
    public static function user(): ?array
    {
        if (self::$user !== null) return self::$user ?: null;
        $id = (int)Session::get('admin_id', 0);
        if (!$id) { self::$user = []; return null; }
        $u = Database::fetch('SELECT id, name, email, role, is_active FROM admin_users WHERE id = ? AND is_active = 1', [$id]);
        self::$user = $u ?: [];
        return $u ?: null;
    }
    public static function attempt(string $email, string $password, string $ip): bool
    {
        // Throttle: max 8 failed attempts per IP per 15 minutes.
        $n = (int)Database::scalar('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [$ip]);
        if ($n >= 8) return false;
        $u = Database::fetch('SELECT * FROM admin_users WHERE email = ? AND is_active = 1', [strtolower($email)]);
        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            Session::set('admin_id', (int)$u['id']);
            Database::query('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?', [$u['id']]);
            Database::query('DELETE FROM login_attempts WHERE ip_address = ?', [$ip]);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) Database::query('UPDATE admin_users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            self::$user = null;
            return true;
        }
        Database::query('INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)', [$ip, substr($email, 0, 190)]);
        return false;
    }
    public static function throttled(string $ip): bool
    {
        return (int)Database::scalar('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)', [$ip]) >= 8;
    }
    public static function logout(): void { Session::destroy(); self::$user = []; }
    public static function require(Request $req): void
    {
        if (self::check()) return;
        if ($req->wantsJson()) Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
        Session::set('_intended', $req->path);
        Response::redirect('/admin/login');
    }
}

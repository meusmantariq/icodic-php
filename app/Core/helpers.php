<?php
declare(strict_types=1);
use App\Core\Config; use App\Core\Session;

function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string
{
    if (preg_match('~^(https?:)?//~', $path) || str_starts_with($path, 'mailto:') || str_starts_with($path, 'tel:') || str_starts_with($path, '#')) return $path;
    static $base = null;
    if ($base === null) $base = base_path_prefix();
    return $base . '/' . ltrim($path, '/');
}
function asset(string $path): string
{
    $file = PUBLIC_PATH . '/' . ltrim($path, '/');
    return url($path) . (is_file($file) ? '?v=' . substr(md5((string)filemtime($file)), 0, 8) : '');
}
function config(string $key, mixed $default = null): mixed { return Config::get($key, $default); }
function old(string $key, mixed $default = ''): string { $o = Session::get('_old', []); return e($o[$key] ?? $default); }
function flash(string $key, mixed $default = null): mixed { return Session::getFlash($key, $default); }
function csrf_field(): string { return App\Core\Csrf::field(); }
function csrf_token(): string { return App\Core\Csrf::token(); }
function t(string $key, ?string $default = null): string { return App\Core\I18n::t($key, $default); }
function current_region(): array { return App\Core\Region::current(); }
function format_price(mixed $usd, ?string $regionCode = null): string { return App\Core\Region::formatPrice((float)($usd ?? 0), $regionCode); }
function local_time(?string $tz): string { try { return (new DateTime('now', new DateTimeZone($tz ?: 'UTC')))->format('D H:i'); } catch (Throwable) { return ''; } }
function json_list(mixed $v): array { if (is_array($v)) return $v; if (!is_string($v) || $v === '') return []; $d = json_decode($v, true); return is_array($d) ? $d : []; }
function str_limit(string $s, int $n = 120): string { return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s; }
function is_admin(): bool { return App\Core\Auth::check(); }
function icon(string $name, string $class = 'ico'): string { return App\Core\Icons::svg($name, $class); }
function img(?string $src): string { if (!$src) return ''; return preg_match('~^(https?:)?//~', $src) ? $src : url($src); }
function setting(string $key, mixed $default = null): mixed { $s = App\Core\View::class; $site = App\Models\Repo::settings(); return $site[$key] ?? $default; }

/** URL prefix when the app lives in a sub-directory. Ignores SCRIPT_NAME values that are not the front controller (php -S quirk). */
function base_path_prefix(): string
{
    static $p = null;
    if ($p !== null) return $p;
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $p = basename($script) === 'index.php' ? rtrim(dirname($script), '/') : '';
    return $p;
}

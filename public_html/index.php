<?php
/**
 * Quantum Nexis — front controller.
 * Deploy layout A (recommended): upload `app/`, `database/`, `storage/` one level ABOVE public_html and the contents of
 * `public_html/` into public_html. Layout B: upload the whole folder into public_html (app/ is protected by .htaccess).
 */
declare(strict_types=1);
// Built-in dev server (php -S 127.0.0.1:8080 index.php): serve existing static files directly.
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    if ($p !== '/' && is_file(__DIR__ . $p)) return false;
    if ($p !== '/' && is_file(__DIR__ . rtrim($p, '/') . '/index.php')) { require __DIR__ . rtrim($p, '/') . '/index.php'; return; }
}
define('DOCROOT', __DIR__);
/** Locate the folder that holds app/ and database/. Supports: app/ next to public_html (A), app/ inside the web root (B),
 *  or the ZIP extracted as a sub-folder (e.g. public_html/icodic-php/app). */
if (!function_exists('qn_find_base')) { function qn_find_base(string $docroot): ?string {
    $c = [dirname($docroot), $docroot];
    foreach (array_merge(glob($docroot . '/*/app/bootstrap.php') ?: [], glob(dirname($docroot) . '/*/app/bootstrap.php') ?: []) as $f) $c[] = dirname(dirname($f));
    foreach ($c as $d) if (is_file($d . '/app/bootstrap.php') && is_dir($d . '/database')) return realpath($d) ?: $d;
    return null;
} }
$base = qn_find_base(__DIR__);
if ($base === null) { http_response_code(500); exit('<h2>Application folders not found.</h2><p>Upload <code>app/</code>, <code>database/</code> and <code>storage/</code> next to index.php (or one level above public_html), then reload. Run <code>/install/</code> to configure.</p>'); }
$appDir = $base . '/app';
require $appDir . '/bootstrap.php';

use App\Core\{Request, Router, View, Response};

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$request = new Request();
$router = new Router();
require $appDir . '/routes.php';

try {
    $router->dispatch($request);
} catch (Throwable $e) {
    error_log($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (App\Core\Config::get('app.debug')) { echo '<pre>' . e($e) . '</pre>'; }
    else { echo View::page('errors.500', ['title' => 'Server error']); }
}

<?php
/**
 * iCodic — front controller.
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
$appDir = is_dir(__DIR__ . '/../app') ? __DIR__ . '/../app' : __DIR__ . '/app';
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

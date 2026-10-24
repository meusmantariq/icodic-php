<?php
declare(strict_types=1);

define('APP_PATH', __DIR__);
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', defined('DOCROOT') ? DOCROOT : BASE_PATH . '/public_html');
define('APP_START', microtime(true));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});

require APP_PATH . '/Core/helpers.php';

$configFile = APP_PATH . '/config/config.php';
if (!is_file($configFile)) {
    // Not installed yet → send to the installer.
    $installUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/install/';
    if (!str_contains($_SERVER['REQUEST_URI'] ?? '', '/install')) {
        header('Location: ' . $installUrl);
        exit;
    }
    return;
}
$config = require $configFile;
App\Core\Config::load($config);

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
mb_internal_encoding('UTF-8');

if (!empty($config['app']['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
ini_set('log_errors', '1');
@mkdir(BASE_PATH . '/storage/logs', 0755, true);
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

App\Core\Session::start();

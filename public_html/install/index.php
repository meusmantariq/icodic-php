<?php
/**
 * Quantum Nexis web installer. Open https://your-domain/install/ once, then delete the /install folder.
 */
declare(strict_types=1);
error_reporting(E_ALL); ini_set('display_errors', '1');
$docroot = dirname(__DIR__);
/** Locate the folder that holds app/ and database/. Supports: app/ next to public_html (A), app/ inside the web root (B),
 *  or the ZIP extracted as a sub-folder (e.g. public_html/icodic-php/app). */
if (!function_exists('qn_find_base')) { function qn_find_base(string $docroot): ?string {
    $c = [dirname($docroot), $docroot];
    foreach (array_merge(glob($docroot . '/*/app/bootstrap.php') ?: [], glob(dirname($docroot) . '/*/app/bootstrap.php') ?: []) as $f) $c[] = dirname(dirname($f));
    foreach ($c as $d) if (is_file($d . '/app/bootstrap.php') && is_dir($d . '/database')) return realpath($d) ?: $d;
    return null;
} }
$base = qn_find_base($docroot);
$layoutProblem = $base === null;
$base = $base ?? dirname($docroot);
$appDir = $base . '/app'; $dbDir = $base . '/database';
$configFile = $appDir . '/config/config.php';
if (is_file($configFile)) { http_response_code(403); exit('<h2>Already installed.</h2><p>Delete <code>app/config/config.php</code> to re-run the installer, and remove this <code>install/</code> folder from your server.</p>'); }

$checks = [
    'PHP ≥ 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
    'PDO MySQL extension' => extension_loaded('pdo_mysql'),
    'mbstring extension' => extension_loaded('mbstring'),
    'openssl extension' => extension_loaded('openssl'),
    'json extension' => extension_loaded('json'),
    'fileinfo extension (uploads)' => extension_loaded('fileinfo'),
    'app/ folder found (app/bootstrap.php)' => is_file($appDir . '/bootstrap.php'),
    'app/config writable' => is_writable($appDir . '/config'),
    'uploads/ writable' => is_writable($docroot . '/uploads'),
    'storage/ writable (will be created)' => is_writable($base) || is_writable($base . '/storage'),
    'schema.sql present' => is_file($dbDir . '/schema.sql'),
    'seed.sql present' => is_file($dbDir . '/seed.sql'),
];
$ok = !in_array(false, $checks, true);
$errors = []; $done = false;
$v = fn($k, $d = '') => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES);
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$guessUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ok) {
    $host = trim($_POST['db_host'] ?? '127.0.0.1'); $port = (int)($_POST['db_port'] ?? 3306); $name = trim($_POST['db_name'] ?? ''); $user = trim($_POST['db_user'] ?? ''); $pass = (string)($_POST['db_pass'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? 'Administrator'); $adminEmail = trim($_POST['admin_email'] ?? ''); $adminPass = (string)($_POST['admin_pass'] ?? '');
    $url = rtrim(trim($_POST['app_url'] ?? $guessUrl), '/'); $seed = !empty($_POST['seed']);
    if (!$name || !$user) $errors[] = 'Database name and user are required.';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email required.';
    if (strlen($adminPass) < 10) $errors[] = 'Admin password must be at least 10 characters.';
    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $run = function (string $file) use ($pdo) {
                $sql = file_get_contents($file);
                $sql = preg_replace('/^--.*$/m', '', $sql);
                foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $stmt) $pdo->exec($stmt);
            };
            $run($dbDir . '/schema.sql');
            if ($seed) $run(is_file($dbDir . '/seed_full.sql') ? $dbDir . '/seed_full.sql' : $dbDir . '/seed.sql'); // full seed (with customer records) ships only in the private ZIP
            $st = $pdo->prepare('INSERT INTO admin_users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, "admin", 1) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), name = VALUES(name)');
            $st->execute([$adminName, $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT)]);
            if ($pdo->query('SELECT COUNT(*) FROM site_settings')->fetchColumn() == 0) $pdo->exec("INSERT INTO site_settings (company_name, hero_tagline) VALUES ('Quantum Nexis LLC', 'Beyond limits IT Solutions.')");
            $pdo->prepare('UPDATE site_settings SET admin_email = ? WHERE admin_email IS NULL OR admin_email = ""')->execute([$adminEmail]);
            $cfg = file_get_contents($appDir . '/config/config.example.php');
            $rep = ['APP_URL_PLACEHOLDER' => $url, 'DB_HOST_PLACEHOLDER' => $host, 'DB_NAME_PLACEHOLDER' => $name, 'DB_USER_PLACEHOLDER' => $user, 'DB_PASS_PLACEHOLDER' => $pass, 'APP_KEY_PLACEHOLDER' => bin2hex(random_bytes(32)), 'ADMIN_EMAIL_PLACEHOLDER' => $adminEmail];
            $cfg = str_replace(array_keys($rep), array_map(fn($x) => addslashes((string)$x), array_values($rep)), $cfg);
            $cfg = preg_replace("/'port'(\s*)=> 3306,/", "'port'$1=> $port,", $cfg, 1);
            if (file_put_contents($configFile, $cfg) === false) throw new RuntimeException('Could not write app/config/config.php');
            @mkdir($base . '/storage/logs', 0755, true); @mkdir($base . '/storage/sessions', 0700, true);
            $done = true;
        } catch (Throwable $e) { $errors[] = $e->getMessage(); }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quantum Nexis installer</title>
<style>body{font-family:system-ui,sans-serif;background:#f8fafc;color:#1f2937;margin:0;padding:2rem}main{max-width:44rem;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:2rem}h1{margin:0 0 .25rem;font-size:1.5rem}.muted{color:#6b7280;font-size:.9rem}label{display:block;margin-top:1rem;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280}input{width:100%;box-sizing:border-box;padding:.6rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;margin-top:.3rem}button{margin-top:1.5rem;background:#4f46e5;color:#fff;border:0;padding:.8rem 1.4rem;border-radius:6px;font-size:.95rem;cursor:pointer}.ok{color:#059669}.bad{color:#dc2626}.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:.75rem 1rem;border-radius:6px;margin-top:1rem}.good{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:1rem;border-radius:6px}table{width:100%;font-size:.9rem;border-collapse:collapse;margin-top:1rem}td{padding:.4rem 0;border-bottom:1px solid #f1f5f9}.row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}code{background:#f1f5f9;padding:.1rem .3rem;border-radius:3px}</style></head><body><main>
<h1>Quantum Nexis — Installer</h1><p class="muted">Standalone PHP 8.2 / MySQL edition. One-time setup.</p>
<?php if ($done): ?>
<div class="good"><b>Installed successfully.</b><br>Database schema created<?= $seed ? ' and all migrated content seeded (118 products, 17 projects, 11 services, 5 ERP solutions, 6 cyber services, offices, testimonials, feed, orders, messages)' : '' ?>.<br>Admin account: <code><?= htmlspecialchars($adminEmail) ?></code></div>
<ol style="margin-top:1.5rem;line-height:1.8"><li><b>Delete the <code>install/</code> folder</b> from the server now.</li><li>Open <a href="<?= htmlspecialchars($url) ?>/">the website</a> and <a href="<?= htmlspecialchars($url) ?>/admin">the admin console</a>.</li><li>Set the mail options in <code>app/config/config.php</code> (SMTP recommended) so order and contact notifications are delivered.</li><li>Optional: add an OpenAI-compatible API key in config to enable the AI live chat (it works in catalog-only mode without one).</li></ol>
<?php else: ?>
<h3>Requirements</h3><table><?php foreach ($checks as $k => $c): ?><tr><td><?= $k ?></td><td class="<?= $c ? 'ok' : 'bad' ?>"><?= $c ? '✓ OK' : '✗ Missing' ?></td></tr><?php endforeach; ?></table>
<?php if (!$ok): ?><div class="err"><?php if ($layoutProblem): ?>The <code>app/</code> and <code>database/</code> folders were not found. Searched: <code><?= htmlspecialchars(dirname($docroot)) ?>/app</code>, <code><?= htmlspecialchars($docroot) ?>/app</code> and any sub-folder of those. Upload the ZIP so that <code>app/</code>, <code>database/</code>, <code>storage/</code> sit either next to <code>index.php</code> in <code><?= htmlspecialchars($docroot) ?></code> (simplest: extract the cPanel ZIP directly into public_html) or one level above it. Then reload.<?php else: ?>Fix the items marked ✗ (cPanel → Select PHP Version / File permissions 755) and reload.<?php endif; ?></div><?php else: ?>
<?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
<form method="post">
<h3 style="margin-top:1.5rem">Database (create it first in cPanel → MySQL® Databases)</h3>
<div class="row"><div><label>Host<input name="db_host" value="<?= $v('db_host', 'localhost') ?>"></label></div><div><label>Port<input name="db_port" value="<?= $v('db_port', '3306') ?>"></label></div></div>
<label>Database name<input name="db_name" value="<?= $v('db_name') ?>" required placeholder="cpaneluser_qnexis"></label>
<div class="row"><div><label>DB user<input name="db_user" value="<?= $v('db_user') ?>" required></label></div><div><label>DB password<input name="db_pass" type="password"></label></div></div>
<label><input type="checkbox" name="seed" value="1" checked style="width:auto;margin-right:.5rem">Import the migrated Base44 content (recommended)</label>
<h3 style="margin-top:1.5rem">Administrator</h3>
<label>Name<input name="admin_name" value="<?= $v('admin_name', 'Usman Tariq') ?>"></label>
<div class="row"><div><label>Email<input name="admin_email" type="email" value="<?= $v('admin_email') ?>" required></label></div><div><label>Password (min 10 chars)<input name="admin_pass" type="password" minlength="10" required></label></div></div>
<h3 style="margin-top:1.5rem">Site</h3>
<label>Public URL<input name="app_url" value="<?= $v('app_url', $guessUrl) ?>" placeholder="https://quantumnexis.si"></label>
<button>Install</button></form>
<?php endif; endif; ?>
</main></body></html>

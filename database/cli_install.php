<?php
/**
 * Non-interactive installer (containers / SSH). Idempotent: creates tables if missing, seeds once, ensures an admin exists.
 * Env: ADMIN_EMAIL, ADMIN_PASSWORD (generated and printed once if empty), SEED_DATA=1|0 (default 1), ADMIN_RESET=1 to force a password reset.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\Database as DB;
$pdo = DB::pdo();
$run = function (string $file) use ($pdo) { $sql = preg_replace('/^--.*$/m', '', file_get_contents($file)); foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $s) $pdo->exec($s); };
$has = fn(string $t) => (bool)$pdo->query("SHOW TABLES LIKE '$t'")->fetchColumn();
if (!$has('admin_users')) { $run(__DIR__ . '/schema.sql'); echo "schema created\n"; } else echo "schema present\n";
if ((getenv('SEED_DATA') ?: '1') === '1' && (int)$pdo->query('SELECT COUNT(*) FROM software_products')->fetchColumn() === 0) { $run(__DIR__ . '/seed.sql'); echo "seed imported\n"; } else echo "seed skipped (already has data or SEED_DATA=0)\n";
if ((int)$pdo->query('SELECT COUNT(*) FROM site_settings')->fetchColumn() === 0) $pdo->exec("INSERT INTO site_settings (company_name, hero_tagline) VALUES ('Quantum Nexis LLC', 'Beyond limits IT Solutions.')");
$email = getenv('ADMIN_EMAIL') ?: 'admin@quantumnexis.si';
if ((int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0) {
    $pass = getenv('ADMIN_PASSWORD') ?: substr(strtr(base64_encode(random_bytes(12)), '+/', 'Aa'), 0, 14) . '!';
    $st = $pdo->prepare('INSERT INTO admin_users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, "admin", 1)');
    $st->execute([getenv('ADMIN_NAME') ?: 'Administrator', $email, password_hash($pass, PASSWORD_DEFAULT)]);
    $pdo->prepare('UPDATE site_settings SET admin_email = ? WHERE admin_email IS NULL OR admin_email = ""')->execute([$email]);
    echo "admin created: $email" . (getenv('ADMIN_PASSWORD') ? '' : " / password: $pass  <-- shown once, change it after login") . "\n";
} elseif (getenv('ADMIN_PASSWORD') && getenv('ADMIN_RESET') === '1') {
    $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE email = ?')->execute([password_hash(getenv('ADMIN_PASSWORD'), PASSWORD_DEFAULT), $email]); echo "admin password reset for $email\n";
} else echo "admin exists\n";
echo "install OK\n";

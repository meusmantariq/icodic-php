#!/usr/bin/env bash
# Writes app/config/config.php from environment variables, installs the DB on first boot, then starts Apache on $PORT.
set -e
cd /var/www/html
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
php deploy/make_config.php
for i in $(seq 1 30); do php -r 'require "app/bootstrap.php"; App\Core\Database::pdo();' >/dev/null 2>&1 && break; echo "waiting for database ($i)"; sleep 2; done
php database/cli_install.php || { echo "Install failed"; exit 1; }
exec apache2-foreground

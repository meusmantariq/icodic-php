#!/usr/bin/env bash
# Starts the embedded MariaDB, generates config.php, installs/seeds the database, then runs Apache on $PORT.
set -e
cd /var/www/html
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

DB_NAME="${DB_NAME:-qnexis}"; DB_USER="${DB_USER:-qnexis}"; DB_PASS="${DB_PASS:-$(openssl rand -hex 12)}"
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
if [ ! -d /var/lib/mysql/mysql ]; then mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-test-db >/dev/null; fi
(mariadbd --user=mysql --datadir=/var/lib/mysql >/var/log/mariadb.log 2>&1 &)
for i in $(seq 1 60); do mariadb-admin ping --silent 2>/dev/null && break; sleep 1; done
mariadb -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS'; ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS'; GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1'; FLUSH PRIVILEGES;"

export DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME DB_USER DB_PASS
php deploy/make_config.php
php database/cli_install.php || { echo "Install failed"; cat storage/logs/php-error.log 2>/dev/null | tail -20; exit 1; }
exec apache2-foreground

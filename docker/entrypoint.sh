#!/bin/sh
# Waits for the database, then sets it up on first run (or adds new migrations later).
set -e
cd /var/www/html
mkdir -p storage/logs storage/cache storage/mail public/uploads
chown -R www-data:www-data storage public/uploads 2>/dev/null || true

echo "Waiting for the database at ${DB_HOST}:${DB_PORT}..."
php /usr/local/bin/yogitech-db-check.php wait

if php /usr/local/bin/yogitech-db-check.php has-migrations; then
  php yogi migrate
else
  echo "First run: creating tables and demo data (php yogi setup)..."
  php yogi setup
fi
chown -R www-data:www-data storage public/uploads 2>/dev/null || true

echo "Yogitech is running at http://localhost:8080"
exec docker-php-entrypoint "$@"

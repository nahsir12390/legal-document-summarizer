#!/bin/sh
set -eu
cd /var/www/html

: "${APP_KEY:?Set APP_KEY in Render environment variables}"
: "${GEMINI_API_KEY:?Set GEMINI_API_KEY in Render environment variables}"
export PORT="${PORT:-10000}"
export DB_CONNECTION=sqlite
export DB_DATABASE=/var/www/html/storage/app/database.sqlite
export FILESYSTEM_DISK=local
export QUEUE_CONNECTION=database
export DB_QUEUE_RETRY_AFTER=240
export APP_URL="${RENDER_EXTERNAL_URL:-${APP_URL:-http://localhost:10000}}"

mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch "$DB_DATABASE"
chown -R www-data:www-data storage bootstrap/cache
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear
php artisan migrate --force
php artisan config:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf

#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction >/dev/null 2>&1 || true
fi

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
fi

# Run database migrations automatically on startup
php artisan migrate --force --no-interaction

# Start queue worker in background loop
(
    set +e
    while true; do
        echo "[$(date)] Starting queue worker..."
        php artisan queue:work database --queue=ai-scan,default --tries=3 --timeout=300 --sleep=3 --no-interaction
        echo "[$(date)] Queue worker stopped with status $?. Restarting in 5 seconds..."
        sleep 5
    done
) >> /var/www/html/storage/logs/queue-worker.log 2>&1 &

# Start scheduler in background loop
(
    set +e
    while true; do
        php artisan schedule:run --no-interaction
        sleep 60
    done
) >> /var/www/html/storage/logs/scheduler.log 2>&1 &

exec "$@"

#!/bin/sh
set -e
mkdir -p /var/log/supervisor

echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 30); do
    if nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; then
        echo "PostgreSQL is ready!"
        break
    fi
    if [ "$i" = "30" ]; then echo "ERROR: PostgreSQL not reachable"; exit 1; fi
    echo "  Attempt $i/30 — retrying in 2s..."
    sleep 2
done
sleep 2

[ ! -L public/storage ] && php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Ready — starting services"
exec "$@"

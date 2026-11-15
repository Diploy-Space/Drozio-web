#!/bin/sh
set -e

cd /var/www/html

# Laravel needs these writable. The code lives in a Docker volume, so the
# permissions are re-applied on every start.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Create the public storage symlink once, if the filesystem allows it.
if [ ! -e public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

exec "$@"

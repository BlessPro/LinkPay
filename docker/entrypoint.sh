#!/bin/sh
set -e

if [ "$1" = "apache2-foreground" ] && [ -f /var/www/html/artisan ]; then
    # Ensure public storage symlink exists (required for uploaded images on Render/Docker).
    if [ ! -e /var/www/html/public/storage ]; then
        ln -s /var/www/html/storage/app/public /var/www/html/public/storage || true
    fi

    # Make sure Laravel can write cache/log/session files.
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

    echo "Running migrations..."
    attempt=1
    while [ $attempt -le 10 ]; do
        set +e
        php artisan migrate --force
        status=$?
        set -e

        if [ $status -eq 0 ]; then
            break
        fi

        echo "Waiting for database... attempt $attempt"
        attempt=$((attempt + 1))
        sleep 5
    done
fi

exec "$@"

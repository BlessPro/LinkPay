#!/bin/sh
set -e

if [ "$1" = "apache2-foreground" ] && [ -f /var/www/html/artisan ]; then
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

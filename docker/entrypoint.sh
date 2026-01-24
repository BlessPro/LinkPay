#!/bin/sh
set -e

if [ "$1" = "apache2-foreground" ]; then
    if [ -f /var/www/html/artisan ]; then
        echo "Running migrations..."
        attempt=1
        until php artisan migrate --force; do
            if [ $attempt -ge 10 ]; then
                echo "Migration failed after $attempt attempts."
                break
            fi
            attempt=$((attempt + 1))
            echo "Waiting for database... attempt $attempt"
            sleep 5
        done
    fi
fi

exec "$@"

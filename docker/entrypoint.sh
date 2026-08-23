#!/bin/sh
set -eu

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    attempts=0
    until php artisan migrate --force; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 20 ]; then
            echo "Database did not become ready in time." >&2
            exit 1
        fi
        sleep 2
    done
fi

php artisan config:cache

exec "$@"

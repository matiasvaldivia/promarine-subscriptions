#!/usr/bin/env sh
set -e
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true

if [ ! -f vendor/autoload.php ]; then
    echo "Dependencias Composer ausentes; instalando antes de iniciar PHP-FPM..."
    composer install --no-interaction --prefer-dist --no-progress
fi

exec "$@"

#!/bin/sh
set -eu

echo "Iniciando aplicación Laravel..."

: "${PORT:=8080}"
export PORT
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY es obligatorio en producción." >&2
    exit 1
fi

echo "Configurando permisos y cachés..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

php artisan optimize:clear

if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force
fi

if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Aplicación lista. Iniciando servidor web..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

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

if [ "${DB_CONNECTION:-}" != "mysql" ]; then
    echo "DB_CONNECTION=mysql es obligatorio. Vincula un servicio MySQL a esta aplicación en Railway." >&2
    exit 1
fi

for database_variable in DB_HOST DB_PORT DB_DATABASE DB_USERNAME; do
    if [ -z "$(printenv "$database_variable")" ]; then
        echo "$database_variable es obligatorio. Revisa las referencias a las variables del servicio MySQL en Railway." >&2
        exit 1
    fi
done

echo "Configurando permisos y cachés..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# No ejecutar cache:clear: CACHE_STORE=database requiere una conexión ya migrada.
php artisan config:clear
php artisan route:clear
php artisan view:clear

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

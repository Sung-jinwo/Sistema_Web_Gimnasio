# ============================================
# Etapa 1: Build de assets con Node
# ============================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copiar package files
COPY package*.json ./

# Instalar dependencias
RUN npm ci

# Copiar código fuente
COPY . .

# Build de assets con Vite
RUN npm run build

# ============================================
# Etapa 2: Imagen final con PHP + Nginx
# ============================================
FROM php:8.2-fpm-alpine

# Instalar extensiones de PHP y dependencias del sistema
RUN apk add --no-cache \
    nginx \
    supervisor \
    gettext \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    git \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    gd \
    zip \
    intl \
    mbstring \
    bcmath \
    opcache \
    && rm -rf /var/cache/apk/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Crear directorios necesarios
RUN mkdir -p /var/www/html /run/nginx /var/log/supervisor

# Copiar código fuente
COPY . /var/www/html

# Copiar assets construidos desde la etapa anterior
COPY --from=node-builder /app/public/build /var/www/html/public/build

WORKDIR /var/www/html

# Instalar dependencias de producción
RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist \
    --no-scripts \
    && composer dump-autoload --optimize

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 public/build 2>/dev/null || true

# Copiar configuraciones
COPY docker/nginx.conf /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

RUN chmod +x /entrypoint.sh /var/www/html/railway/*.sh

# Railway reemplaza PORT en producción; 8080 permite ejecutar la imagen localmente.
ENV PORT=8080
ENV RUN_MIGRATIONS=false

EXPOSE 8080

# Comando de inicio
CMD ["/entrypoint.sh"]

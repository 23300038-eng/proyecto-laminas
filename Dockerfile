FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    postgresql-client \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar el proyecto
COPY . /var/www/html

# Crear directorios necesarios con permisos amplios
RUN mkdir -p /var/www/html/public/uploads/usuarios \
    && mkdir -p /var/www/html/public/uploads/carrusel \
    && mkdir -p /var/www/html/app/data/cache \
    && mkdir -p /var/run/php-fpm \
    && mkdir -p /var/log \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/run/php-fpm \
    && chmod -R 777 /var/www/html/app/data \
    && chmod -R 777 /var/www/html/public/uploads \
    && touch /var/log/php-fpm.log \
    && chown www-data:www-data /var/log/php-fpm.log

WORKDIR /var/www/html/app

# Instalar dependencias de Laminas
RUN composer install --no-dev --optimize-autoloader

# Copiar configuración de nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Copiar configuración de PHP-FPM
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copiar script de inicio
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/docker-entrypoint.sh"]
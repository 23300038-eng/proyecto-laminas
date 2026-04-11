FROM php:8.2-fpm

# Instalar Nginx y extensiones necesarias
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar todo el proyecto
COPY . .

# Instalar dependencias de PHP (sin dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar configuraciones
COPY nginx.conf /etc/nginx/nginx.conf

# Copiar php-fpm.conf (nombre correcto de tu archivo)
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Crear directorios y permisos
RUN mkdir -p /var/run/php-fpm \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/run/php-fpm

EXPOSE 80

# Copiar y dar permisos al entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
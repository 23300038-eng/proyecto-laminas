FROM php:8.2-fpm

# Instalar dependencias + Nginx
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
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar configuraciones
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf   # ← importante

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/run/php-fpm \
    && chown www-data:www-data /var/run/php-fpm

EXPOSE 80

# Usar tu entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
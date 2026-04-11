FROM php:8.2-fpm

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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# ←←← ESTA ES LA LÍNEA QUE CAMBIAMOS
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction

COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/run/php-fpm \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/run/php-fpm
    && mkdir -p /var/www/html/data/cache \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 0755 /var/www/html/data

EXPOSE 80

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
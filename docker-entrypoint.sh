#!/bin/bash
set -e

echo "=== Starting Novafarma ==="

# Crear directorios
mkdir -p /var/run/php-fpm
chown -R www-data:www-data /var/run/php-fpm /var/www/html

# Asegurar que el directorio de caché existe y es escribible
mkdir -p /var/www/html/data/cache
chown -R www-data:www-data /var/www/html/data
chmod -R 0755 /var/www/html/data
# Asegurar escritura amplia por si el runtime usa otro usuario en Railway
chmod -R 0777 /var/www/html/data/cache || true

echo "✓ PostgreSQL config detected"

# Iniciar PHP-FPM
echo "Starting PHP-FPM..."
php-fpm -D

# Pequeña espera para que PHP-FPM arranque
sleep 2

echo "PHP-FPM started successfully"

# Verificar Nginx
nginx -t

echo "Starting Nginx..."
exec nginx -g 'daemon off;'
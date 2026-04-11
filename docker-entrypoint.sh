#!/bin/bash
set -e

echo "=== Starting Novafarma ==="

# Crear directorios
mkdir -p /var/run/php-fpm
chown -R www-data:www-data /var/run/php-fpm /var/www/html

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
#!/bin/bash
set -e

echo "=== Starting Novafarma ==="

# Crear directorio del socket
mkdir -p /var/run/php-fpm
chown www-data:www-data /var/run/php-fpm

echo "✓ PostgreSQL config detected"

# Limpiar caché de Laminas (opcional)
php ./public/index.php --clear-config-cache || true

# Iniciar PHP-FPM en background
echo "Starting PHP-FPM..."
php-fpm -D

# Esperar a que el socket esté listo
echo "Waiting for PHP-FPM socket..."
timeout=20
while [ ! -S /var/run/php-fpm.sock ] && [ $timeout -gt 0 ]; do
    sleep 1
    timeout=$((timeout-1))
done

if [ ! -S /var/run/php-fpm.sock ]; then
    echo "ERROR: PHP-FPM socket not found!"
    exit 1
fi

echo "PHP-FPM started successfully"

# Verificar Nginx
nginx -t

echo "Starting Nginx..."
exec nginx -g 'daemon off;'
#!/bin/bash
set -e

echo "=== Starting Novafarma ==="

# Crear socket
mkdir -p /var/run/php-fpm
chown www-data:www-data /var/run/php-fpm

echo "✓ PostgreSQL config detected"

# Limpiar caché (no falla el contenedor si hay error)
echo "Limpiando caché de configuración..."
if [ -f ./app/public/index.php ]; then
    php ./app/public/index.php --clear-config-cache 2>/dev/null || true
else
    echo "Advertencia: No se encontró app/public/index.php"
fi

# Iniciar PHP-FPM
echo "Starting PHP-FPM..."
php-fpm -D

# Esperar socket
echo "Waiting for PHP-FPM socket..."
timeout=20
while [ ! -S /var/run/php-fpm.sock ] && [ $timeout -gt 0 ]; do
    sleep 1
    timeout=$((timeout-1))
done

if [ ! -S /var/run/php-fpm.sock ]; then
    echo "ERROR: PHP-FPM socket not found!"
    ls -la /var/run/
    exit 1
fi

echo "PHP-FPM started successfully"

nginx -t
echo "Starting Nginx..."
exec nginx -g 'daemon off;'
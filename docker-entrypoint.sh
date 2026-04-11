#!/bin/bash
set -e

echo "=== Starting Novafarma ==="

# Crear directorio del socket
mkdir -p /var/run/php-fpm
chown www-data:www-data /var/run/php-fpm

# Configuración PostgreSQL (Railway usa variables como DATABASE_URL o PG* )
echo "✓ PostgreSQL config detected"

# Limpiar cache de Laminas si existe
php /var/www/html/public/index.php --clear-cache || true

# Iniciar PHP-FPM en background
php-fpm -D
echo "Starting PHP-FPM..."

# Esperar a que el socket esté listo
timeout=30
while [ ! -S /var/run/php-fpm.sock ] && [ $timeout -gt 0 ]; do
    echo "Waiting for PHP-FPM socket..."
    sleep 1
    timeout=$((timeout-1))
done

if [ ! -S /var/run/php-fpm.sock ]; then
    echo "ERROR: PHP-FPM socket not created!"
    exit 1
fi

echo "PHP-FPM started successfully"

# Verificar configuración Nginx
nginx -t

echo "Starting Nginx..."
exec nginx -g 'daemon off;'
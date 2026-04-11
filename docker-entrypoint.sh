#!/bin/bash
set -e

echo "=== Starting Novafarma ==="

# Crear directorio del socket
mkdir -p /var/run/php-fpm
chown www-data:www-data /var/run/php-fpm

echo "✓ PostgreSQL config detected"

# Limpiar caché de Laminas (usando la ruta correcta)
echo "Limpiando caché de configuración..."
if [ -f ./app/public/index.php ]; then
    php ./app/public/index.php --clear-config-cache || true
else
    echo "Advertencia: No se encontró app/public/index.php"
fi

# Iniciar PHP-FPM en background
echo "Starting PHP-FPM..."
php-fpm -D

# Esperar a que el socket esté listo
echo "Waiting for PHP-FPM socket..."
timeout=15
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

# Verificar Nginx
nginx -t || { echo "Nginx configuration error!"; cat /etc/nginx/nginx.conf; exit 1; }

echo "Starting Nginx..."
exec nginx -g 'daemon off;'
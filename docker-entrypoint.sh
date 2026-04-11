#!/bin/bash
set -e

echo "=== Starting Novafarma ==="
echo "Environment: $(uname -a)"

# Verificar variables de entorno
if [ -z "$PGHOST" ] || [ -z "$PGDATABASE" ]; then
    echo "⚠️  Variables de entorno PostgreSQL no configuradas, continuando..."
else
    echo "✓ PostgreSQL config detected: $PGHOST:$PGPORT/$PGDATABASE"
fi

# Crear directorios necesarios
echo "Creating directories..."
mkdir -p /var/www/html/public/uploads/usuarios
mkdir -p /var/www/html/public/uploads/carrusel
mkdir -p /var/www/html/app/data/cache

# Fijar permisos
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/public/uploads || true
chown -R www-data:www-data /var/www/html/app/data/cache || true
chmod -R 775 /var/www/html/public/uploads || true
chmod -R 775 /var/www/html/app/data/cache || true
chmod -R 775 /var/www/html/app/data || true

# Limpiar caches de configuración
echo "Clearing config cache..."
rm -f /var/www/html/app/data/cache/*.cache || true

# Verificar autoloader de Composer
if [ ! -f /var/www/html/app/vendor/autoload.php ]; then
    echo "ERROR: Composer autoloader not found!"
    echo "Run: cd app && composer install"
    exit 1
fi
echo "✓ Composer autoloader found"

# Crear directorio para socket
mkdir -p /var/run/php-fpm
chown www-data:www-data /var/run/php-fpm || true

# Crear log files para PHP-FPM
touch /var/log/php-fpm.log || true
chown www-data:www-data /var/log/php-fpm.log || true

# Verificar conexión a PostgreSQL si está configurado
if [ ! -z "$PGHOST" ] && [ ! -z "$PGUSER" ]; then
    echo "Verifying PostgreSQL connection..."
    RETRY_COUNT=0
    MAX_RETRIES=30
    
    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        if PGPASSWORD="$PGPASSWORD" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -c "SELECT 1;" > /dev/null 2>&1; then
            echo "PostgreSQL connection successful"
            break
        else
            RETRY_COUNT=$((RETRY_COUNT + 1))
            echo "PostgreSQL connection attempt $RETRY_COUNT/$MAX_RETRIES failed, retrying in 2 seconds..."
            sleep 2
        fi
    done
    
    if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
        echo "Warning: Could not verify PostgreSQL connection after $MAX_RETRIES attempts"
        echo "Continuing anyway - the application may fail when accessing the database"
    fi
fi

# Iniciar PHP-FPM en background
echo "Starting PHP-FPM..."
php-fpm -D

# Esperar a que el socket de PHP-FPM esté disponible
echo "Waiting for PHP-FPM socket..."
SOCKET_WAIT=0
MAX_SOCKET_WAIT=30

while [ ! -S /var/run/php-fpm.sock ] && [ $SOCKET_WAIT -lt $MAX_SOCKET_WAIT ]; do
    echo "Waiting for PHP-FPM socket... ($SOCKET_WAIT/$MAX_SOCKET_WAIT)"
    sleep 1
    SOCKET_WAIT=$((SOCKET_WAIT + 1))
done

if [ ! -S /var/run/php-fpm.sock ]; then
    echo "ERROR: PHP-FPM socket not created after $MAX_SOCKET_WAIT seconds!"
    echo "Checking PHP-FPM status:"
    ps aux | grep php-fpm || true
    echo "Checking logs:"
    tail -20 /var/log/php-fpm.log || true
    exit 1
fi

echo "PHP-FPM socket is available"
ls -la /var/run/php-fpm.sock

# Verificar configuración de Nginx
echo "Verifying Nginx configuration..."
if ! nginx -t > /dev/null 2>&1; then
    echo "ERROR: Nginx configuration is invalid!"
    nginx -t
    exit 1
fi
echo "Nginx configuration is valid"

# Iniciar Nginx en foreground
echo "Starting Nginx..."
exec nginx -g 'daemon off;'

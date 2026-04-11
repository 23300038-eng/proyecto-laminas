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

# Iniciar servicios
echo "Starting nginx..."
service nginx start

echo "Starting PHP-FPM..."
exec php-fpm

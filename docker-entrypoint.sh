#!/bin/bash
set -e

echo "Starting Novafarma..."

# Verificar variables de entorno
if [ -z "$PGHOST" ] || [ -z "$PGDATABASE" ]; then
    echo "ERROR: Variables de entorno PostgreSQL no configuradas"
    exit 1
fi

# Crear directorios necesarios
mkdir -p /var/www/html/public/uploads/usuarios
mkdir -p /var/www/html/public/uploads/carrusel
mkdir -p /var/www/html/app/data/cache

# Fijar permisos
chown -R www-data:www-data /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/app/data/cache
chmod -R 775 /var/www/html/public/uploads
chmod -R 775 /var/www/html/app/data/cache

# Ejecutar migraciones si es necesario
if [ ! -f /var/www/html/db-migrated ]; then
    echo "Ejecutando SQL inicial..."
    psql -h "$PGHOST" -p "${PGPORT:-5432}" -U "$PGUSER" -d "$PGDATABASE" -f /var/www/html/database.sql || true
    touch /var/www/html/db-migrated
fi

# Iniciar servicios
echo "Iniciando nginx..."
service nginx start

echo "Iniciando PHP-FPM..."
exec php-fpm

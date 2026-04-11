# 🔧 Soluciones para Error 502 en Railway

## Problemas Identificados

### 1. **Socket PHP-FPM no está siendo escuchado por Nginx**
El archivo `nginx.conf` intenta conectarse a `127.0.0.1:9000`, pero en contenedores Docker/Railway, esto puede no funcionar correctamente cuando PHP-FPM está corriendo en el mismo proceso.

### 2. **Nginx escucha en puerto 8080 pero Railway expone puerto automáticamente**
Mismatch de puertos: La aplicación está configurada para puerto 8080 pero hay conflictos en el contenedor.

### 3. **Script de inicio no espera a PHP-FPM antes de iniciar Nginx**
El `docker-entrypoint.sh` inicia Nginx como servicio y luego ejecuta PHP-FPM con `exec`, pero Nginx puede no estar conectando correctamente.

### 4. **Posibles problemas con permisos de cache**
La configuración de Railway (`railway.production.php`) desactiva caché, pero los archivos podrían estar siendo creados con permisos incorrectos.

---

## Soluciones Implementadas

### Solución 1: Usar Socket Unix en lugar de TCP
Unix sockets son más confiables en contenedores.

### Solución 2: Mejorar el script de inicio
Asegurar que PHP-FPM esté completamente iniciado antes de que Nginx intente conectarse.

### Solución 3: Revisar directorios de cache y permisos

---

## Pasos para Arreglar

### Paso 1: Actualizar `nginx.conf`

Cambiar de TCP socket a Unix socket:

```conf
fastcgi_pass unix:/var/run/php-fpm.sock;
```

### Paso 2: Actualizar `php-fpm.conf`

Configurar PHP-FPM para usar Unix socket en lugar de TCP:

```ini
listen = /var/run/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

### Paso 3: Mejorar `docker-entrypoint.sh`

Hacer que espere correctamente y maneje señales:

```bash
#!/bin/bash
set -e

# ... otros comandos ...

# Crear directorio para socket
mkdir -p /var/run/php-fpm
chown www-data:www-data /var/run/php-fpm || true

# Iniciar PHP-FPM en background
php-fpm -D

# Esperar a que el socket esté disponible
while [ ! -S /var/run/php-fpm.sock ]; do
    echo "Esperando socket de PHP-FPM..."
    sleep 1
done

echo "✓ Socket PHP-FPM disponible"

# Iniciar Nginx en foreground
exec nginx -g 'daemon off;'
```

### Paso 4: Verificar conexión a BD

Agregar verificación de conexión a PostgreSQL en el script:

```bash
# Antes de iniciar nginx
if [ ! -z "$PGHOST" ]; then
    echo "Verificando conexión a PostgreSQL..."
    until psql -h "$PGHOST" -U "$PGUSER" -d "$PGDATABASE" -c "SELECT 1" > /dev/null 2>&1; do
        echo "PostgreSQL no disponible, esperando..."
        sleep 2
    done
    echo "✓ Conexión a PostgreSQL establecida"
fi
```

---

## Checklist de Verificación en Railway

1. **Variables de Entorno** ✓
   - PGHOST
   - PGPORT
   - PGDATABASE
   - PGUSER
   - PGPASSWORD

2. **Redeploy** 
   - Menú ⋮ → Redeploy
   - Esperar hasta que diga "Deployment successful"

3. **Revisar Logs**
   - Railway → Build Logs (durante el build)
   - Railway → Deploy Logs (después del deploy)
   - Railway → Runtime Logs (errores en vivo)

4. **Probar URLs**
   - `https://tu-proyecto.up.railway.app/` → Página de inicio
   - `https://tu-proyecto.up.railway.app/auth/login` → Login
   - `https://tu-proyecto.up.railway.app/health` (si existe) → Status

---

## Comandos Útiles en Railway

### Ver logs en tiempo real
```bash
# En Railway Console
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
tail -f /var/log/php-fpm.log
```

### Verificar PHP-FPM
```bash
ps aux | grep php
ls -la /var/run/php-fpm.sock
```

### Verificar Nginx
```bash
nginx -t  # Verificar sintaxis
ps aux | grep nginx
netstat -tulpn | grep 8080
```

### Verificar BD
```bash
psql "postgresql://$PGUSER:$PGPASSWORD@$PGHOST:$PGPORT/$PGDATABASE" -c "SELECT 1;"
```

---

## Si aún hay problemas 502

### Debug Level 1: Nginx no puede hablar con PHP-FPM
Síntoma: Todos los requests dan 502
Solución: Verificar que socket exista: `ls -la /var/run/php-fpm.sock`

### Debug Level 2: PHP-FPM está crasheando
Síntoma: Socket no existe o aparece y desaparece
Solución: Ver logs: `tail -f /var/log/php-fpm.log`

### Debug Level 3: Problema de permisos
Síntoma: Access denied al cache
Solución: `chmod -R 777 /var/www/html/app/data`

### Debug Level 4: Problema de BD
Síntoma: Error "could not connect to server"
Solución: Verificar variables PGHOST, PGPORT, PGUSER, PGPASSWORD

---

## Referencia Rápida de Puertos

| Servicio | Local | Container | Railway |
|----------|-------|-----------|---------|
| Nginx | 8080 | 8080 | Auto (443/80) |
| PHP-FPM | N/A | 9000/socket | N/A |
| PostgreSQL | 5432 | 5432 | Externo |


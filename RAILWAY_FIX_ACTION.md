# 🚀 Guía para Arreglar Error 502 en Railway - Acción Inmediata

## ✅ Cambios Realizados

Se han actualizado 4 archivos clave para arreglar el error 502:

### 1. **nginx.conf**
- ✅ Cambió de socket TCP (`127.0.0.1:9000`) a **Unix socket** (`/var/run/php-fpm.sock`)
- ✅ Agregados timeouts para PHP-FPM
- ✅ Agregado endpoint `/health` para monitoreo

### 2. **php-fpm.conf**
- ✅ Configurado para usar **Unix socket** en lugar de TCP
- ✅ Agregados permisos correctos para el socket
- ✅ Agregado logging y timeouts

### 3. **docker-entrypoint.sh**
- ✅ Espera correctamente a que PHP-FPM esté listo
- ✅ Verifica conexión a PostgreSQL con reintentos
- ✅ Verifica configuración de Nginx antes de iniciar
- ✅ Ejecuta Nginx en foreground (mejor para Railway)

### 4. **Dockerfile**
- ✅ Crea directorios necesarios
- ✅ Crea archivo de log para PHP-FPM

### 5. **health.php**
- ✅ Nuevo endpoint para verificar estado de la aplicación

---

## 🔧 Próximos Pasos en Railway

### Paso 1: Commit y Push de los Cambios

```bash
cd /home/miguel/Documentos/SISTEMAWEB/proyecto-laminas-main
git add -A
git commit -m "Fix: Socket Unix en Nginx y mejorar docker-entrypoint para eliminar 502"
git push origin main  # o tu rama
```

### Paso 2: Redeploy en Railway

1. Ve a **Railway Dashboard**
2. Selecciona tu proyecto `proyecto-laminas`
3. Haz clic en el menú **⋮** (tres puntos)
4. Selecciona **Redeploy**
5. Espera a que diga "Deployment successful" (aprox. 2-3 minutos)

### Paso 3: Verificar Variables de Entorno

Asegúrate que Railway tenga EXACTAMENTE estas variables:

```
PGDATABASE    = railway          (o el nombre de tu BD)
PGHOST        = metro.proxy.rlwy.net  (o tu host)
PGPASSWORD    = [tu_password_aqui]
PGPORT        = 46812            (o el puerto correcto)
PGUSER        = postgres
```

**Dónde verificar:**
- Railway Dashboard → Variables → Environment

---

## 🧪 Pruebas para Verificar que Funciona

### Test 1: Endpoint de Salud (sin autenticación)

```bash
curl https://proyecto-laminas.up.railway.app/health
```

Deberías ver:
```json
{
  "status": "OK",
  "timestamp": "2026-04-10 14:30:45",
  "checks": {
    "php": {
      "version": "8.2.x",
      "status": "OK"
    },
    "extensions": {
      "pdo": "OK",
      "pdo_pgsql": "OK",
      "json": "OK"
    },
    "cache_dir": "OK",
    "uploads_dir": "OK",
    "database": "OK"
  }
}
```

### Test 2: Página Principal

```bash
curl -I https://proyecto-laminas.up.railway.app/
```

Deberías ver: `HTTP/2 200 OK`

### Test 3: Formulario de Login

```bash
curl -I https://proyecto-laminas.up.railway.app/auth/login
```

Deberías ver: `HTTP/2 200 OK`

### Test 4: Acceso a Dashboard (requiere login)

```bash
curl -I https://proyecto-laminas.up.railway.app/dashboard
```

Deberías ver: `HTTP/2 302 Found` (redirect a login)

---

## 📊 Ver Logs en Railway

### Durante el Deploy

1. Railway Dashboard → Build Logs
2. Busca estos mensajes:
   ```
   ✓ Composer autoloader found
   ✓ PostgreSQL connection successful
   ✓ PHP-FPM socket is available
   ✓ Nginx configuration is valid
   ✓ Starting Nginx...
   ```

### Después del Deploy

1. Railway Dashboard → Deploy Logs o Runtime Logs
2. Busca errores con:
   ```bash
   tail -f /var/log/nginx/error.log
   tail -f /var/log/php-fpm.log
   ```

---

## 🐛 Si Aún Hay Error 502

### Debug Level 1: Ver Logs de Nginx

En Railway Console:
```bash
tail -50 /var/log/nginx/error.log
tail -50 /var/log/nginx/access.log
```

### Debug Level 2: Ver Logs de PHP-FPM

En Railway Console:
```bash
tail -50 /var/log/php-fpm.log
```

### Debug Level 3: Verificar Socket

En Railway Console:
```bash
ls -la /var/run/php-fpm.sock
ps aux | grep php-fpm
```

### Debug Level 4: Verificar BD

En Railway Console:
```bash
psql "postgresql://$PGUSER:$PGPASSWORD@$PGHOST:$PGPORT/$PGDATABASE" -c "SELECT 1;"
```

### Debug Level 5: Probar PHP Directamente

En Railway Console:
```bash
php -r "phpinfo();" | head -20
php -r "echo extension_loaded('pdo_pgsql') ? 'OK' : 'FALTA';"
```

---

## 🔄 Diferencias Principales del Fix

| Antes | Ahora |
|-------|-------|
| TCP socket (127.0.0.1:9000) | Unix socket (/var/run/php-fpm.sock) |
| nginx start (daemon) | nginx -g 'daemon off;' (foreground) |
| No espera a PHP-FPM | Espera hasta 30 segundos a que socket esté listo |
| No verifica BD | Verifica conexión a PostgreSQL con reintentos |
| Sin health check | Endpoint /health disponible |
| Poco logging | Logging detallado en cada paso |

---

## ✅ Checklist Final

- [ ] Cambios commiteados y pusheados
- [ ] Railway iniciando redeploy
- [ ] Deployment dijo "successful"
- [ ] Endpoint `/health` responde con 200
- [ ] Página principal carga
- [ ] Login carga
- [ ] Dashboard requiere autenticación (302 redirect)
- [ ] Sin errores 502

---

## 📞 Si No Funciona

1. **Copia los logs completos** de Railway
2. **Verifica las variables de entorno** una vez más
3. **Intenta un force redeploy**:
   - Railway Dashboard → Redeploy
   - Selecciona "Rebuild from source"
4. **Si nada funciona**, tienes opciones:
   - Verificar si PostgreSQL está correctamente conectado
   - Revisar si el repositorio tiene todos los archivos
   - Limpiar caché: `rm -rf app/data/cache/*`


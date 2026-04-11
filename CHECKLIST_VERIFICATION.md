# ✅ CHECKLIST - Verificación del Fix 502

## Fase 1: Antes de Subir a Railway

### Verificaciones Locales
- [ ] Descargaste los cambios
- [ ] `docker-entrypoint.sh` es ejecutable
  ```bash
  ls -la docker-entrypoint.sh | grep "^-rwx"
  ```
- [ ] `test-deployment.sh` es ejecutable
  ```bash
  ls -la test-deployment.sh | grep "^-rwx"
  ```
- [ ] Archivos modificados no tienen caracteres raros
  ```bash
  file nginx.conf php-fpm.conf docker-entrypoint.sh
  # Debería mostrar: ASCII text
  ```

### Verificar Cambios
- [ ] `nginx.conf` contiene `fastcgi_pass unix:/var/run/php-fpm.sock;`
  ```bash
  grep "fastcgi_pass unix" nginx.conf
  ```
- [ ] `php-fpm.conf` contiene `listen = /var/run/php-fpm.sock`
  ```bash
  grep "listen = /var/run/php-fpm.sock" php-fpm.conf
  ```
- [ ] `docker-entrypoint.sh` contiene nueva lógica de sincronización
  ```bash
  grep "Wait for PHP-FPM socket" docker-entrypoint.sh
  ```
- [ ] `health.php` existe en `app/public/`
  ```bash
  ls -la app/public/health.php
  ```

### Documentación
- [ ] `README_FIX_502.md` existe
- [ ] `RAILWAY_FIX_ACTION.md` existe
- [ ] `FIX_502_ERROR.md` existe
- [ ] `502_ERROR_EXPLANATION.md` existe
- [ ] `DIAGRAMS_FIX_502.md` existe

---

## Fase 2: Subiendo a Railway

### Git Operations
- [ ] Cambios listos para commit
  ```bash
  git status
  ```
- [ ] Commit realizado
  ```bash
  git log --oneline -1
  ```
- [ ] Push completado
  ```bash
  git log --oneline origin/main -1
  ```
- [ ] Railway detectó el push
  - Railway Dashboard debe mostrar "Building..."

### Durante el Build
- [ ] Build started automáticamente
  - Railway Dashboard → Build Logs
  - Debe mostrar "Building..." con timestamp

- [ ] Verificar logs de build cada 30 segundos
  ```
  ✓ Composer autoloader found      (esperado en ~30s)
  ✓ PostgreSQL connection verified (esperado en ~40s)
  ✓ PHP-FPM socket is available    (esperado en ~45s)
  ✓ Nginx configuration is valid   (esperado en ~46s)
  ✓ Starting Nginx...              (esperado en ~47s)
  ```

- [ ] Build completó correctamente
  - Último mensaje: "Deployment successful"
  - Esperado: 2-3 minutos total

- [ ] No hay errores en logs
  - Buscar: "ERROR", "FAIL", "exit 1"
  - Deberían estar en 0

---

## Fase 3: Después del Deploy

### Health Check
- [ ] Endpoint `/health` responde
  ```bash
  curl https://tu-proyecto.up.railway.app/health | json_pp
  ```

- [ ] Response contiene todos los checks OK
  ```json
  {
    "status": "OK",
    "checks": {
      "php": {"status": "OK"},
      "extensions": {"pdo": "OK", "pdo_pgsql": "OK"},
      "cache_dir": "OK",
      "uploads_dir": "OK",
      "database": "OK"
    }
  }
  ```

### Páginas Principales
- [ ] Home page carga
  ```bash
  curl -I https://tu-proyecto.up.railway.app/ | head -1
  # Esperado: HTTP/2 200 OK
  ```

- [ ] Login page carga
  ```bash
  curl -I https://tu-proyecto.up.railway.app/auth/login | head -1
  # Esperado: HTTP/2 200 OK
  ```

- [ ] Home page contiene "NOVAFARMA"
  ```bash
  curl https://tu-proyecto.up.railway.app/ | grep -i novafarma
  ```

- [ ] Login page contiene formulario
  ```bash
  curl https://tu-proyecto.up.railway.app/auth/login | grep -i "login\|password"
  ```

### Verificar Ausencia de 502
- [ ] Ninguna página retorna 502
  ```bash
  for url in "/" "/auth/login" "/health" "/nonexistent"; do
    echo "Testing $url:"
    curl -s -w "Status: %{http_code}\n" -o /dev/null https://tu-proyecto.up.railway.app$url
  done
  ```

- [ ] Logs de Nginx no contienen "bad gateway"
  - Railway Console: `grep "bad gateway" /var/log/nginx/error.log`
  - Esperado: 0 coincidencias

- [ ] Logs de PHP-FPM sin errores
  - Railway Console: `tail -20 /var/log/php-fpm.log`
  - Verificar: sin "connection refused"

### Testing Automático
- [ ] Script de testing funciona localmente
  ```bash
  ./test-deployment.sh https://tu-proyecto.up.railway.app
  ```

- [ ] Todos los tests pasan (6/6)
  ```
  ✓ Passed: 6
  ✗ Failed: 0
  ```

---

## Fase 4: Funcionamiento Real

### Operaciones Normales
- [ ] Puedes acceder a login sin errores
- [ ] Puedes ingresar credenciales
- [ ] Dashboard carga correctamente
- [ ] Puedes navegar entre páginas
- [ ] Uploads funcionan (si existen)

### Bajo Carga
- [ ] Múltiples requests simultáneos OK
  ```bash
  for i in {1..10}; do
    curl -s https://tu-proyecto.up.railway.app/ > /dev/null &
  done
  wait
  # Verificar: todos 200 OK, ningún 502
  ```

- [ ] Sin timeout de PHP-FPM
  ```bash
  # Railway Console
  grep "timeout" /var/log/php-fpm.log
  # Esperado: 0 coincidencias
  ```

### Recuperación de Fallos
- [ ] PostgreSQL se reconecta después de caída
- [ ] Sin errores si BD se reinicia
- [ ] Application recover sin manual redeploy

---

## Fase 5: Monitoreo Continuo

### Daily Checks
- [ ] `/health` endpoint sigue respondiendo 200
- [ ] Logs de error.log vacíos o sin "502"
- [ ] Ningún crash de PHP-FPM

### Weekly Review
- [ ] Estadísticas de uptime
  - Railway Dashboard → Deployments
  - Esperado: > 99%

- [ ] Logs de acceso normales
  - Patrones esperados sin anomalías

- [ ] Performance
  - Response times consistentes
  - Sin ralentizaciones

---

## 🔄 Si Algo Falla

### 502 persiste
- [ ] Revisar logs BUILD
  ```bash
  Railway Dashboard → Build Logs
  Buscar: ERROR, FAIL
  ```

- [ ] Revisar logs RUNTIME
  ```bash
  Railway Console:
  tail -50 /var/log/nginx/error.log
  tail -50 /var/log/php-fpm.log
  ```

- [ ] Verificar socket
  ```bash
  Railway Console:
  ls -la /var/run/php-fpm.sock
  ps aux | grep php-fpm
  ```

- [ ] Force Redeploy
  ```bash
  Railway Dashboard → Redeploy
  Marcar "Rebuild from source"
  ```

### PostgreSQL connection error
- [ ] Verificar variables de entorno
  - Railway Dashboard → Variables
  - PGHOST, PGPORT, PGUSER, PGPASSWORD, PGDATABASE

- [ ] Probar conexión manualmente
  ```bash
  Railway Console:
  psql "postgresql://$PGUSER:$PGPASSWORD@$PGHOST:$PGPORT/$PGDATABASE" -c "SELECT 1;"
  ```

- [ ] Verificar IP/Network
  - Railway PostgreSQL debe estar en "Public Network"

### Permission denied en socket
- [ ] Verificar permisos
  ```bash
  Railway Console:
  ls -la /var/run/php-fpm.sock
  # Esperado: www-data:www-data con permisos 660
  ```

- [ ] Reintentar con corrección manual
  ```bash
  Railway Console:
  chmod 660 /var/run/php-fpm.sock
  chown www-data:www-data /var/run/php-fpm.sock
  ```

---

## 📋 Comandos Útiles para Railway Console

```bash
# Ver logs en tiempo real
tail -f /var/log/nginx/error.log
tail -f /var/log/php-fpm.log
tail -f /var/log/nginx/access.log

# Verificar procesos
ps aux | grep -E "nginx|php|postgres"

# Verificar sockets
ls -la /var/run/php-fpm.sock

# Verificar conexión BD
psql "postgresql://$PGUSER:$PGPASSWORD@$PGHOST:$PGPORT/$PGDATABASE" -c "SELECT version();"

# Verificar configuración
nginx -T
php-fpm -t

# Verificar directorios
du -sh /var/www/html/app/data/cache
du -sh /var/www/html/public/uploads

# Test PHP
php -r "phpinfo();" | grep -A5 "pdo_pgsql"
```

---

## ✅ Checklist Completo Satisfecho

Cuando hayas completado todo:

- [ ] Fase 1: Verificaciones locales OK
- [ ] Fase 2: Deploy a Railway OK
- [ ] Fase 3: Post-deploy OK
- [ ] Fase 4: Funcionamiento normal OK
- [ ] Fase 5: Monitoreo establecido

**¡Felicidades! Tu aplicación está correctamente desplegada en Railway sin errores 502.**


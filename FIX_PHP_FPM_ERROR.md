# 🔧 Corrección de Error - PHP-FPM Configuration

## ❌ El Problema

El contenedor crasheaba con este error:

```
ERROR: [/usr/local/etc/php-fpm.d/www.conf:21] unknown entry 'error_log'
ERROR: Unable to include /usr/local/etc/php-fpm.d/www.conf
ERROR: failed to load configuration file '/usr/local/etc/php-fpm.conf'
ERROR: FPM initialization failed
```

## 🔍 Causa Raíz

En el archivo `php-fpm.conf` que modifiqué, agregué dos líneas de logging:

```ini
; Logging
error_log = /var/log/php-fpm.log
log_level = warning
```

**Problema:** La opción `error_log` **no existe** en PHP-FPM 8.2 dentro de la sección `[www]` (pool configuration).

### Diferencia Importante

En PHP-FPM, las opciones de logging funcionan diferente según dónde se configuren:

| Ubicación | Opción | Soportada |
|-----------|--------|-----------|
| Global (antes de `[www]`) | `error_log` | ✓ Sí |
| Global (antes de `[www]`) | `log_level` | ✓ Sí |
| Sección `[www]` | `error_log` | ✗ NO |
| Sección `[www]` | `log_level` | ✗ NO |

Colocar estas opciones dentro de la sección `[www]` causa el crash porque PHP-FPM no reconoce esas directivas en ese contexto.

## ✅ La Solución

Simplemente **remover las dos líneas problemáticas**:

```diff
  catch_workers_output = yes
  decorate_workers_output = no

- ; Logging
- error_log = /var/log/php-fpm.log
- log_level = warning
-
  ; Timeouts
  request_terminate_timeout = 300
```

**Archivo corregido:**
```ini
[www]
listen = /var/run/php-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
listen.backlog = 65535

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 10
pm.max_requests = 500

clear_env = no

catch_workers_output = yes
decorate_workers_output = no

; Timeouts
request_terminate_timeout = 300
```

## 📤 Cambios Realizados

✅ Commit: `Fix: Remover opciones de logging inválidas en PHP-FPM 8.2`
✅ Archivo: `php-fpm.conf`
✅ Push: Completado a main

## 🚀 Próximo Paso

Ahora necesitas hacer **redeploy en Railway**:

1. Railway Dashboard → tu proyecto
2. Menú ⋮ → **Redeploy**
3. Esperar 2-3 minutos

El contenedor **debe iniciar correctamente ahora** sin errores de configuración.

## 🧪 Verificación Posterior

Después del redeploy, verifica:

```bash
# Health check
curl https://tu-proyecto.up.railway.app/health

# O script de testing
./test-deployment.sh https://tu-proyecto.up.railway.app
```

Deberías ver:
```json
{
  "status": "OK",
  "checks": {
    "php": {"status": "OK"},
    "extensions": {"pdo": "OK", "pdo_pgsql": "OK"},
    "database": "OK"
  }
}
```

---

**Lo siento por este error inicial. PHP-FPM tiene reglas estrictas para opciones de logging y necesitan estar en el nivel global, no dentro de pools. Ya está arreglado.**


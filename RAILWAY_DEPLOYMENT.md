# 🚀 Deployment en Railway - Guía Rápida

## ✅ Estado Actual
- ✓ Contenedor Docker corriendo
- ✓ PHP-FPM escuchando en puerto 9000
- ✓ Nginx respondiendo en puerto 8080
- ✓ PostgreSQL conectado

## 🔧 Si aún tienes 502

### Paso 1: Variables de Entorno en Railway
Verifica que existan EXACTAMENTE estas 5 variables:

```
PGDATABASE    → railway
PGHOST        → metro.proxy.rlwy.net
PGPASSWORD    → [tu password]
PGPORT        → 46812
PGUSER        → postgres
```

### Paso 2: Redeploy
1. Ve a Railway → tu servicio (proyecto-laminas)
2. Menú (⋮) → **Redeploy**
3. Espera ~2 minutos

### Paso 3: Verifica los logs
En Railway → Build Logs:
```
✓ Starting Novafarma
✓ Creating directories...
✓ Setting permissions...
✓ Clearing config cache...
✓ Composer autoloader found
✓ Starting nginx...
✓ Starting PHP-FPM...
```

## 🌐 URLs para Testear

| URL | Esperado |
|-----|----------|
| `https://proyecto-laminas.up.railway.app/` | Página de bienvenida Novafarma |
| `https://proyecto-laminas.up.railway.app/auth/login` | Formulario de login |
| `https://proyecto-laminas.up.railway.app/security/perfil` | Lista de perfiles (sin login = redirect) |

## 🔐 Credenciales Test
```
Usuario: admin
Contraseña: admin123
```

## 📊 Si aún hay errores

### Opción A: Ver error específico
1. Ve a Railway → HTTP Logs
2. Busca los requests con status NO 200
3. Abre el inspector para ver detalles

### Opción B: Reiniciar todo desde cero
```bash
# Local
cd app
composer install

# En Railway: Redeploy
```

### Opción C: Limpiar cache
```bash
# En Railway console
rm -rf /var/www/html/app/data/cache/*
```

## 🐛 Debugging

### Ver logs en tiempo real
```bash
# En Railway → Deploy Logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

### Probar conexión BD
```bash
psql "postgresql://postgres:PASSWORD@metro.proxy.rlwy.net:46812/railway" -c "SELECT COUNT(*) FROM usuario;"
```

## ✅ Confirmación de Éxito

Si ves en HOME page:
- ✓ Logo "NOVAFARMA"
- ✓ "Sistema Administrativo para Farmacéutica"
- ✓ Botón "IR AL LOGIN"
- ✓ Sin errores en consola

**¡Significa que está correctamente desplegado!**

---

Si aún tienes 502, comparte:
1. Los primeros 20 líneas de los Build Logs
2. Las variables de entorno configuradas
3. El URL exacto donde ves el 502

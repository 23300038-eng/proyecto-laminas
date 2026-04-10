# 🔧 NOVAFARMA - Configuración & Instalación

## Requisitos del Sistema

### Mínimos
- **PHP**: 8.0 o superior
- **PostgreSQL**: 10 o superior
- **Composer**: 2.0 o superior
- **Navegador**: Moderno (Chrome, Firefox, Safari, Edge)

### Recomendados
- **PHP**: 8.2 o superior
- **PostgreSQL**: 14 o superior
- **RAM**: 2GB mínimo
- **Almacenamiento**: 500MB mínimo

---

## Instalación Paso a Paso

### 1. Preparar el Ambiente

#### En Linux/macOS:
```bash
# Clonar repositorio
git clone <tu-repo> proyecto-laminas
cd proyecto-laminas

# Crear estructura de directorios
mkdir -p app/data/cache
mkdir -p public/uploads/usuarios
mkdir -p public/uploads/carrusel

# Permisos de escritura
chmod -R 777 app/data/cache
chmod -R 777 public/uploads
```

#### En Windows (PowerShell como Administrador):
```powershell
# Clonar repositorio
git clone <tu-repo> proyecto-laminas
cd proyecto-laminas

# Crear directorios
New-Item -ItemType Directory -Path "app\data\cache" -Force
New-Item -ItemType Directory -Path "public\uploads\usuarios" -Force
New-Item -ItemType Directory -Path "public\uploads\carrusel" -Force
```

### 2. Instalar Dependencias

```bash
cd app
composer install --no-interaction --prefer-dist
```

### 3. Configurar Base de Datos

#### PostgreSQL Connection String

```env
# archivo .env en raíz del proyecto
PGHOST=localhost
PGPORT=5432
PGDATABASE=novafarma
PGUSER=postgres
PGPASSWORD=tu_password_seguro
```

#### Crear Base de Datos

```bash
# Usando psql
psql -U postgres -c "CREATE DATABASE novafarma;"

# Importar script SQL
psql -U postgres -d novafarma -f database.sql
```

#### Verificar conexión

```bash
psql -U postgres -d novafarma -c "\dt"
```

Deberías ver las tablas: `usuario`, `perfil`, `modulo`, `permisos_perfil`, etc.

### 4. Configurar PHP

#### Archivo: `php.ini`

```ini
; Session Configuration
session.name = NOVAFARMA_SESSID
session.use_only_cookies = 1
session.cookie_httponly = 1
session.gc_maxlifetime = 3600      ; 1 hora
session.cookie_lifetime = 0         ; Cookie de sesión

; Date/Time
date.timezone = America/Mexico_City  ; Cambiar según tu zona

; File Upload
upload_max_filesize = 10M
post_max_size = 10M
```

### 5. Iniciar Servidor

#### Opción 1: PHP Built-in Server (Desarrollo)

```bash
cd app
php -S localhost:8000 -t public/
```

#### Opción 2: Docker (Recomendado)

```bash
# Desde raíz del proyecto
docker-compose up -d
```

#### Opción 3: Nginx + PHP-FPM (Producción)

Ver configuración en `nginx/default.conf`

```bash
# Iniciar servicios
sudo systemctl start nginx
sudo systemctl start php-fpm
```

### 6. Verificar Instalación

1. Abrir navegador: `http://localhost:8000`
2. Deberías ver página de bienvenida de Novafarma
3. Clic en "Iniciar Sesión"
4. Ingresar: `admin` / `admin123`
5. Si funciona, verás el listado de Perfiles

---

## Configuración Avanzada

### Variables de Entorno

Crear archivo `.env` (o usar variables del servidor):

```env
# Database
PGHOST=localhost
PGPORT=5432
PGDATABASE=novafarma
PGUSER=postgres
PGPASSWORD=password_seguro

# Application
APP_ENV=development          # development, staging, production
APP_DEBUG=true              # true o false
APP_NAME=Novafarma

# Security
JWT_SECRET=tu_secret_jwt_aqui
UPLOAD_MAX=10485760         # 10MB en bytes

# Email (Para futuro)
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587
MAIL_USERNAME=correo@ejemplo.com
MAIL_PASSWORD=password_correo
```

### Optimización de Rendimiento

#### Cache de Configuración (Producción)

```php
// app/config/application.config.php
return [
    // ...
    'module_listener_options' => [
        'config_cache_enabled' => true,        // ✓ Habilitar en producción
        'config_cache_key' => 'app.config.cache',
        'module_map_cache_enabled' => true,    // ✓ Habilitar en producción
        'module_map_cache_key' => 'app.module.cache',
        'cache_dir' => 'data/cache/',
    ],
];
```

#### Limpiar Cache

```bash
rm -rf app/data/cache/*
```

### Configuración de Logs

```php
// Crear archivo de logs
touch app/logs/error.log
touch app/logs/access.log

// Permisos
chmod 666 app/logs/*.log
```

---

## Seguridad en Producción

### 1. HTTPS

```nginx
# En nginx
server {
    listen 443 ssl http2;
    ssl_certificate /ruta/a/certificado.crt;
    ssl_certificate_key /ruta/a/clave.key;
}
```

### 2. Headers de Seguridad

```php
// En app/config/application.config.php
'view_manager' => [
    'base_path' => '',
],

// Agregar en .htaccess (Apache)
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

### 3. Permissions

```bash
# Archivos públicos
chmod 755 public/

# Archivos privados
chmod 700 app/config/
chmod 700 app/module/

# Uploads
chmod 755 public/uploads/
```

### 4. Credenciales

```bash
# CAMBIAR las credenciales por defecto
# En base de datos, actualizar:
UPDATE usuario SET str_pwd = crypt('nueva_password', gen_salt('bf')) 
WHERE str_nombre_usuario = 'admin';
```

---

## Troubleshooting

### Error: "SQLSTATE[08006]"
- PostgreSQL no está corriendo
- Verificar conexión: `psql -U postgres -d novafarma`

### Error: "Class 'Laminas\Db\Adapter\Adapter' not found"
- Ejecutar: `composer install`
- Verificar: `composer dump-autoload`

### Error: "No such file or directory" (Uploads)
```bash
mkdir -p public/uploads/{usuarios,carrusel}
chmod -R 777 public/uploads
```

### Error: "Session cookie couldn't be sent"
- PHP está enviando headers ya
- Mover `session_start()` antes de cualquier output

### Error: 404 en rutas
- Limpiar cache: `rm -rf app/data/cache/*`
- Verificar módulos en `app/config/modules.config.php`

### Error: Imagen no se carga
- Verificar ruta en BD: `/uploads/usuarios/imagen.jpg`
- Verificar archivo existe: `ls -la public/uploads/usuarios/`

---

## Configuración por Entorno

### Development

```env
APP_DEBUG=true
APP_ENV=development
CONFIG_CACHE_ENABLED=false
```

```php
// Configuración adicional
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Staging

```env
APP_DEBUG=false
APP_ENV=staging
CONFIG_CACHE_ENABLED=true
```

### Production

```env
APP_DEBUG=false
APP_ENV=production
CONFIG_CACHE_ENABLED=true
```

```bash
# Compilar cache
cd app
composer install --optimize-autoloader --no-dev
php bin/clear-config-cache.php
```

---

## Backup & Restauración

### Backup de Base de Datos

```bash
# Backup completo
pg_dump -U postgres novafarma > backup_novafarma.sql

# Backup con compresión
pg_dump -U postgres novafarma | gzip > backup_novafarma.sql.gz
```

### Restaurar de Backup

```bash
# Restaurar desde archivo SQL
psql -U postgres novafarma < backup_novafarma.sql

# Restaurar desde gzip
gunzip < backup_novafarma.sql.gz | psql -U postgres novafarma
```

### Backup de Uploads

```bash
# Comprimir directorio de uploads
tar -czf uploads_backup.tar.gz public/uploads/
```

---

## Docker Setup (Opcional)

Si usas Docker, el archivo `docker-compose.yml` incluye:
- PostgreSQL 14
- PHP 8.2-FPM
- Nginx

```bash
# Levantar servicios
docker-compose up -d

# Logs
docker-compose logs -f

# Entrar a consola PHP
docker-compose exec app bash

# Ejecutar SQL
docker-compose exec db psql -U postgres -d novafarma -f /database.sql
```

---

## Performance Tips

1. **Habilitar OPcache**
```ini
opcache.enable=1
opcache.memory_consumption=128
```

2. **Usar índices en BD**
```sql
CREATE INDEX idx_usuario_perfil ON usuario(id_perfil);
CREATE INDEX idx_permisos_modulo ON permisos_perfil(id_modulo);
```

3. **Lazy Load de módulos**
- Registrar solo módulos necesarios en `modules.config.php`

4. **Minificar assets**
- CSS y JS en `public/` están listos para minificación

---

## Checklist de Instalación

- [ ] PHP 8.0+ instalado
- [ ] PostgreSQL 10+ instalado
- [ ] Composer instalado
- [ ] Dependencias instaladas (`composer install`)
- [ ] Base de datos creada (`database.sql`)
- [ ] Usuario `admin` creado con contraseña
- [ ] Carpeta `public/uploads/` con permisos 777
- [ ] Carpeta `app/data/cache/` con permisos 777
- [ ] Variables de entorno configuradas (`.env`)
- [ ] Servidor iniciado (PHP o Docker)
- [ ] Acceso a `http://localhost:8000` funciona
- [ ] Login con `admin` / `admin123` funciona

---

**¡Listo para usar Novafarma!** 🚀

Para más información, ver `NOVAFARMA_README.md`

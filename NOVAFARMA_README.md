# 🏥 NOVAFARMA - Sistema Administrativo para Farmacéutica

Sistema web corporativo completo, desarrollado con **Laminas Framework (PHP)**, diseñado para la gestión integral de una farmacéutica con control de permisos dinámico, módulos personalizables y CRUD completos.

---

## 📋 Tabla de Contenidos

1. [Características](#características)
2. [Requisitos](#requisitos)
3. [Instalación](#instalación)
4. [Estructura del Proyecto](#estructura-del-proyecto)
5. [Módulos Disponibles](#módulos-disponibles)
6. [Base de Datos](#base-de-datos)
7. [Autenticación](#autenticación)
8. [Sistema de Permisos](#sistema-de-permisos)
9. [Guía de Uso](#guía-de-uso)
10. [API de Rutas](#api-de-rutas)

---

## ✨ Características

✅ **Autenticación segura** con validación de usuario, contraseña y estado  
✅ **Control de permisos dinámicos** basado en roles/perfiles  
✅ **Módulos personalizables** que pueden ser creados y modificados en tiempo real  
✅ **CRUD completos** para: Perfil, Usuario, Módulo, Permisos-Perfil  
✅ **Paginación** de 5 registros por página  
✅ **Upload de imágenes** para usuarios  
✅ **Breadcrumbs** obligatorios en todas las vistas  
✅ **Interfaz minimalista** con colores neutros (blanco, gris, azul suave)  
✅ **Diseño responsivo** adaptable a dispositivos móviles  
✅ **Validaciones** en todos los formularios  
✅ **Base de datos PostgreSQL** con relaciones completas  

---

## 📦 Requisitos

- **PHP 8.0+**
- **PostgreSQL 10+**
- **Composer**
- **Node.js** (opcional, para herramientas de desarrollo)

---

## 🔧 Instalación

### 1. Clonar el repositorio

```bash
git clone <tu-repo>
cd proyecto-laminas-main
```

### 2. Instalar dependencias

```bash
cd app
composer install
```

### 3. Configurar variables de entorno

Crear archivo `.env` en la raíz del proyecto:

```env
PGHOST=localhost
PGPORT=5432
PGDATABASE=novafarma
PGUSER=postgres
PGPASSWORD=tu_password
```

### 4. Crear la base de datos

```bash
# Conectarse a PostgreSQL
psql -U postgres

# Crear base de datos
CREATE DATABASE novafarma;

# Conectarse a la BD
\c novafarma

# Ejecutar script de inicialización
\i /ruta/a/database.sql
```

### 5. Iniciar el servidor

```bash
cd app
php -S localhost:8000 -t public/
```

Abrir en el navegador: **http://localhost:8000**

---

## 📁 Estructura del Proyecto

```
proyecto-laminas-main/
├── database.sql                 # Script de base de datos
├── docker-compose.yml           # Configuración Docker
├── app/
│   ├── config/
│   │   ├── application.config.php
│   │   ├── modules.config.php
│   │   └── autoload/
│   │       └── global.php
│   ├── module/
│   │   ├── Application/         # Módulo principal
│   │   ├── Auth/                # Módulo de autenticación
│   │   ├── Security/            # Módulo de seguridad (CRUD)
│   │   └── Dashboard/           # Módulo de demostración
│   ├── public/
│   │   ├── index.php
│   │   ├── css/
│   │   ├── js/
│   │   └── uploads/
│   └── data/
│       └── cache/
├── nginx/                       # Configuración nginx
└── README.md                    # Este archivo
```

---

## 🎯 Módulos Disponibles

### 1. **Auth** (Autenticación)
- **Ruta**: `/auth/login`
- **Funcionalidad**: Login con validación de usuario, contraseña y estado
- **Credenciales de prueba**: `admin` / `admin123`

### 2. **Security** (Seguridad - CRUD Completos)

#### 🔹 Perfil
- **Ruta**: `/security/perfil`
- **Acciones**: Listar, Crear, Editar, Eliminar, Ver Detalle
- **Tabla**: `perfil`

#### 🔹 Módulo
- **Ruta**: `/security/modulo`
- **Acciones**: Listar, Crear, Editar, Eliminar, Ver Detalle
- **Tabla**: `modulo`

#### 🔹 Usuario
- **Ruta**: `/security/usuario`
- **Acciones**: Listar, Crear, Editar, Eliminar, Ver Detalle
- **Tabla**: `usuario`
- **Especial**: Upload de imagen de perfil

#### 🔹 Permisos-Perfil
- **Ruta**: `/security/permiso-perfil`
- **Acciones**: Listar, Crear, Editar, Eliminar, Ver Detalle
- **Tabla**: `permisos_perfil`
- **Nota**: Sin filtrado avanzado

### 3. **Dashboard** (Módulos de Demostración)

#### 🔹 Principal 1
- **Ruta**: `/principal1/item1` y `/principal1/item2`
- **Funcionalidad**: Solo UI, sin base de datos

#### 🔹 Principal 2
- **Ruta**: `/principal2/item1` y `/principal2/item2`
- **Funcionalidad**: Solo UI, sin base de datos

---

## 🗄️ Base de Datos

### Tablas Principales

#### `perfil`
```sql
id (PK)
str_nombre_perfil (UNIQUE)
bit_administrador
descripcion
creado_en
actualizado_en
```

#### `usuario`
```sql
id (PK)
str_nombre_usuario (UNIQUE)
id_perfil (FK)
str_pwd
id_estado_usuario (FK)
str_correo (UNIQUE)
str_numero_celular
imagen
ultimo_login
creado_en
actualizado_en
```

#### `modulo`
```sql
id (PK)
str_nombre_modulo (UNIQUE)
str_icono
int_orden
bit_activo
creado_en
actualizado_en
```

#### `permisos_perfil`
```sql
id (PK)
id_modulo (FK)
id_perfil (FK)
bit_agregar
bit_editar
bit_consulta
bit_eliminar
bit_detalle
creado_en
actualizado_en
```

#### `submodulo`
```sql
id (PK)
id_modulo (FK)
str_nombre_submodulo
str_ruta
int_orden
bit_activo
creado_en
actualizado_en
```

#### `menu`
```sql
id (PK)
id_menu (FK, relación padre-hijo)
id_submodulo (FK)
str_nombre_menu
int_orden
bit_activo
creado_en
actualizado_en
```

#### `estado_usuario`
```sql
id (PK)
str_nombre
creado_en
```

### Script de Inicialización

Ver archivo `database.sql` para crear todas las tablas, índices, constraints y datos iniciales.

---

## 🔐 Autenticación

### Login
1. Navegar a `/auth/login`
2. Ingresar usuario y contraseña
3. Sistema valida:
   - Existencia del usuario
   - Contraseña correcta (hash bcrypt)
   - Usuario activo
4. Si es válido, crea sesión PHP y redirige a `/security/perfil`

### Logout
- Destruir sesión: `/auth/logout`
- Redirige a `/auth/login`

### Seguridad
- Las contraseñas se almacenan en hash bcrypt
- Validación de estado de usuario (Activo/Inactivo/Suspendido)
- Actualización de `ultimo_login` automática

---

## 🔑 Sistema de Permisos

### Estructura
Un **Usuario** tiene un **Perfil**, que a su vez tiene **Permisos** sobre **Módulos**.

### Permisos Disponibles
- `bit_agregar`: Crear nuevos registros
- `bit_editar`: Modificar registros existentes
- `bit_consulta`: Ver/consultar registros
- `bit_eliminar`: Eliminar registros
- `bit_detalle`: Ver detalle completo

### Ejemplo
1. Usuario "Juan" tiene perfil "Gerente"
2. Perfil "Gerente" tiene permiso sobre módulo "Seguridad" con:
   - `bit_consulta = true` (puede ver)
   - `bit_editar = true` (puede modificar)
   - `bit_agregar = false` (no puede crear)
   - `bit_eliminar = false` (no puede eliminar)
3. Los botones en UI se muestran/ocultan según estos permisos

---

## 💡 Guía de Uso

### Acceso a Módulos

**Página de Inicio**
```
GET /
```

**Autenticación**
```
GET /auth/login              # Formulario de login
POST /auth/login             # Procesar login
GET /auth/logout             # Cerrar sesión
```

**Seguridad - Perfil**
```
GET /security/perfil         # Listar perfiles (con paginación)
GET /security/perfil-add     # Formulario crear
POST /security/perfil-add    # Guardar nuevo
GET /security/perfil-edit/:id   # Formulario editar
POST /security/perfil-edit/:id  # Guardar cambios
GET /security/perfil-delete/:id # Eliminar
GET /security/perfil-detalle/:id # Ver detalles
```

**Seguridad - Módulo**
```
GET /security/modulo         # Similar a perfil
GET /security/modulo-add
POST /security/modulo-add
GET /security/modulo-edit/:id
POST /security/modulo-edit/:id
GET /security/modulo-delete/:id
GET /security/modulo-detalle/:id
```

**Seguridad - Usuario**
```
GET /security/usuario
GET /security/usuario-add
POST /security/usuario-add      # Con upload de imagen
GET /security/usuario-edit/:id
POST /security/usuario-edit/:id # Con upload de imagen
GET /security/usuario-delete/:id
GET /security/usuario-detalle/:id
```

**Seguridad - Permisos-Perfil**
```
GET /security/permiso-perfil
GET /security/permiso-perfil-add
POST /security/permiso-perfil-add
GET /security/permiso-perfil-edit/:id
POST /security/permiso-perfil-edit/:id
GET /security/permiso-perfil-delete/:id
GET /security/permiso-perfil-detalle/:id
```

---

## 🎨 Diseño

### Colores
- **Primario**: Azul suave (#1a73e8)
- **Secundario**: Gris neutro (#f5f5f5, #ddd, #333)
- **Peligro**: Rojo (#d32f2f)
- **Fondo**: Blanco (#fff)

### Componentes
- **Breadcrumbs**: Obligatorios en todas las vistas
- **Paginación**: 5 registros por página
- **Formularios**: Validados con mensajes de error
- **Botones CRUD**: Siempre visibles (Create, Read, Update, Delete)
- **Tablas**: Diseño limpio y responsivo

---

## 🚀 Extensiones Futuras

1. **JWT para API**: Implementar autenticación token-based
2. **CAPTCHA**: Agregar verificación en login
3. **Reportes**: Generación de PDF/Excel
4. **Auditoría**: Log de cambios en el sistema
5. **Notificaciones**: Email en eventos importantes
6. **Exportación**: CSV, Excel, PDF
7. **Gráficos**: Dashboards con estadísticas

---

## 📞 Soporte

Para consultas o problemas, contactar a:
- **Email**: support@novafarma.com
- **Documentación**: Ver archivos en `/app/module/*/`

---

## 📄 Licencia

Proyecto desarrollado para Novafarma - Todos los derechos reservados.

---

**Última actualización**: 10 de abril de 2026  
**Versión**: 1.0.0  
**Estado**: En desarrollo

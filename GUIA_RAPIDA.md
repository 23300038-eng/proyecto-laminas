# 🚀 Guía Rápida - Novafarma

## Inicio Rápido

### 1. Acceso al Sistema

**URL Principal**: `http://localhost:8000`

### 2. Credenciales de Prueba

```
Usuario: admin
Contraseña: admin123
```

### 3. Flujo de Acceso

```
1. http://localhost:8000              ← Página de bienvenida
2. http://localhost:8000/auth/login   ← Ingresar credenciales
3. http://localhost:8000/security/perfil ← Dashboard de seguridad
```

---

## 📍 Navegación Rápida

| Sección | URL | Descripción |
|---------|-----|-------------|
| **Inicio** | `/` | Página de bienvenida |
| **Login** | `/auth/login` | Formulario de autenticación |
| **Logout** | `/auth/logout` | Cerrar sesión |
| **Perfil** | `/security/perfil` | CRUD de perfiles |
| **Módulo** | `/security/modulo` | CRUD de módulos |
| **Usuario** | `/security/usuario` | CRUD de usuarios |
| **Permisos** | `/security/permiso-perfil` | CRUD de permisos |
| **Principal 1** | `/principal1/item1` | Demo módulo 1.1 |
| **Principal 2** | `/principal2/item1` | Demo módulo 2.1 |

---

## 🎯 Tareas Comunes

### Crear un nuevo usuario

1. Ir a `/security/usuario`
2. Clic en "+ Crear Usuario"
3. Llenar formulario:
   - Nombre de usuario (ej: juan.lopez)
   - Correo (ej: juan@novafarma.com)
   - Contraseña (mínimo 8 caracteres)
   - Seleccionar perfil
   - Seleccionar estado
   - Opcional: Subir imagen
4. Clic en "Guardar"

### Asignar permisos a un perfil

1. Ir a `/security/permiso-perfil`
2. Clic en "+ Crear Permiso"
3. Seleccionar módulo y perfil
4. Marcar permisos deseados:
   - ☑ Agregar / Crear
   - ☑ Editar / Modificar
   - ☑ Consultar / Ver
   - ☑ Eliminar
   - ☑ Ver Detalle
5. Clic en "Guardar"

### Crear un nuevo módulo

1. Ir a `/security/modulo`
2. Clic en "+ Crear Módulo"
3. Llenar:
   - Nombre del módulo
   - Icono (opcional)
   - Orden (para ordenamiento en menú)
   - Marcar "Activo"
4. Clic en "Guardar"

### Crear un nuevo perfil

1. Ir a `/security/perfil`
2. Clic en "+ Crear Perfil"
3. Llenar:
   - Nombre del perfil
   - Descripción
   - Marcar "Es administrador" si es necesario
4. Clic en "Guardar"

---

## 💾 Base de Datos

### Tabla de Usuarios Iniciales

| Usuario | Contraseña | Perfil | Estado |
|---------|-----------|--------|--------|
| admin | admin123 | Administrador | Activo |

### Perfiles Iniciales

| Perfil | Descripción | Administrador |
|--------|------------|---------------|
| Administrador | Acceso total al sistema | ✓ |
| Gerente | Acceso limitado a módulos principales | ✗ |
| Empleado | Acceso solo a consultas | ✗ |
| Auditor | Acceso de lectura a todos los módulos | ✗ |

### Estados de Usuario

| Estado | Descripción |
|--------|------------|
| Activo | Usuario puede acceder |
| Inactivo | Usuario bloqueado temporalmente |
| Suspendido | Usuario bloqueado permanentemente |

---

## 🔧 Configuración del Sistema

### Variables de Entorno (.env)

```bash
PGHOST=localhost
PGPORT=5432
PGDATABASE=novafarma
PGUSER=postgres
PGPASSWORD=tu_password
```

### Ubicación de Uploads

```
/public/uploads/usuarios/       ← Imágenes de perfil
/public/uploads/carrusel/       ← Imágenes del carrusel (antiguo)
```

---

## 📋 Checklist de Características

### ✅ Implementado

- [x] Autenticación con usuario/contraseña
- [x] Validación de estado de usuario
- [x] Hash de contraseñas (bcrypt)
- [x] CRUD Perfil
- [x] CRUD Módulo
- [x] CRUD Usuario (con upload de imagen)
- [x] CRUD Permisos-Perfil
- [x] Paginación (5 registros)
- [x] Breadcrumbs en todas las vistas
- [x] Diseño minimalista responsivo
- [x] Base de datos PostgreSQL completa
- [x] Script SQL de inicialización
- [x] Validaciones en formularios
- [x] Menú dinámico

### 🔄 En Consideración

- [ ] JWT para APIs
- [ ] CAPTCHA en login
- [ ] Generación de reportes
- [ ] Sistema de auditoría
- [ ] Notificaciones por email
- [ ] Gráficos/Dashboard
- [ ] Búsqueda avanzada

---

## 🆘 Troubleshooting

### Error: "No hay perfiles registrados"
- Verificar que la base de datos se ejecutó correctamente
- Revisar que se insertaron datos en `database.sql`

### Error: "Usuario no encontrado"
- Verificar que el usuario existe en la BD
- Revisar contraseña (es sensible a mayúsculas/minúsculas)

### Error: "No se puede eliminar este perfil"
- Significa que hay usuarios asignados a este perfil
- Asignar los usuarios a otro perfil primero

### Error: Imágenes no se cargan
- Verificar que la carpeta `public/uploads/usuarios/` existe
- Verificar permisos de escritura de la carpeta

### Error: Sesión se cierra automáticamente
- Verificar configuración de sesiones en PHP
- Aumentar `session.gc_maxlifetime` en `php.ini`

---

## 📚 Recursos

- **Framework**: [Laminas](https://www.laminas.dev/)
- **BD**: PostgreSQL
- **PHP**: 8.0+
- **Frontend**: Vanilla JS + CSS3

---

## 📞 Contacto & Soporte

**Proyecto**: Novafarma v1.0.0  
**Fecha**: 10 de abril de 2026  
**Estado**: ✅ Funcional

---

**¡Gracias por usar Novafarma!** 🎉

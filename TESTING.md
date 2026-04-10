# 🧪 Testing - Novafarma

## Plan de Pruebas

Este documento describe los casos de prueba para el sistema Novafarma.

---

## 1️⃣ Autenticación

### TC-001: Login Exitoso
```
Pasos:
1. Ir a http://localhost:8000/auth/login
2. Ingresar: Usuario = "admin", Contraseña = "admin123"
3. Clic en "Iniciar Sesión"

Resultado Esperado:
- Redirigir a /security/perfil
- Mostrar lista de perfiles
- Sesión iniciada ($_SESSION['usuario_id'] poblado)
```

### TC-002: Login con Usuario Incorrecto
```
Pasos:
1. Ir a http://localhost:8000/auth/login
2. Ingresar: Usuario = "usuario_inexistente", Contraseña = "cualquier"
3. Clic en "Iniciar Sesión"

Resultado Esperado:
- Mostrar error: "Usuario no encontrado"
- Permanecer en formulario de login
- No crear sesión
```

### TC-003: Login con Contraseña Incorrecta
```
Pasos:
1. Ir a http://localhost:8000/auth/login
2. Ingresar: Usuario = "admin", Contraseña = "incorrecta"
3. Clic en "Iniciar Sesión"

Resultado Esperado:
- Mostrar error: "Contraseña incorrecta"
- Permanecer en formulario
```

### TC-004: Login con Usuario Inactivo
```
Pasos:
1. Cambiar estado de usuario a "Inactivo" en BD
2. Ir a http://localhost:8000/auth/login
3. Ingresar credenciales del usuario
4. Clic en "Iniciar Sesión"

Resultado Esperado:
- Mostrar error: "Usuario inactivo o suspendido"
- No permitir acceso
```

### TC-005: Logout
```
Pasos:
1. Estar logueado
2. Ir a /auth/logout

Resultado Esperado:
- Sesión destruida
- Redirigir a /auth/login
- No poder acceder a rutas protegidas
```

---

## 2️⃣ CRUD Perfil

### TC-006: Listar Perfiles
```
Pasos:
1. Loguearse como admin
2. Ir a /security/perfil

Resultado Esperado:
- Ver tabla con perfiles
- Paginación funciona (5 registros por página)
- Botones CRUD visibles (👁️ ✏️ 🗑️)
- Breadcrumbs visibles: Inicio / Seguridad / Perfil
```

### TC-007: Crear Perfil
```
Pasos:
1. En /security/perfil, clic "+ Crear Perfil"
2. Llenar: Nombre = "Vendedor", Descripción = "Vende productos"
3. Marcar "Es administrador" = No
4. Clic "Guardar"

Resultado Esperado:
- Perfil creado exitosamente
- Redirigir a listado de perfiles
- Nuevo perfil visible en la tabla
```

### TC-008: Editar Perfil
```
Pasos:
1. En listado de perfiles, clic icono ✏️ de un perfil
2. Cambiar nombre: "Vendedor" → "Vendedor Premium"
3. Clic "Actualizar"

Resultado Esperado:
- Perfil actualizado en BD
- Cambio visible en listado
- Redirigir a listado
```

### TC-009: Ver Detalle de Perfil
```
Pasos:
1. En listado, clic icono 👁️
2. Ver página de detalle

Resultado Esperado:
- Mostrar todos los datos del perfil
- Botones: Editar, Volver
- Información clara y legible
```

### TC-010: Eliminar Perfil
```
Pasos:
1. En listado, clic icono 🗑️
2. Confirmar eliminación

Resultado Esperado:
- Si no hay usuarios: Perfil eliminado
- Si hay usuarios: Mostrar error "No se puede eliminar"
```

---

## 3️⃣ CRUD Módulo

### TC-011: Listar Módulos
```
Pasos:
1. Ir a /security/modulo

Resultado Esperado:
- Ver tabla con módulos existentes
- Paginación de 5 registros
- Botones CRUD funcionales
```

### TC-012: Crear Módulo
```
Pasos:
1. Clic "+ Crear Módulo"
2. Llenar:
   - Nombre = "Inventario"
   - Icono = "package"
   - Orden = 10
   - Activo = ✓
3. Clic "Guardar"

Resultado Esperado:
- Módulo creado en BD
- Visible en listado
```

### TC-013: Editar Módulo
```
Pasos:
1. Clic ✏️ en un módulo
2. Cambiar orden: 10 → 5
3. Clic "Actualizar"

Resultado Esperado:
- Cambios guardados
- Lista reordenada
```

### TC-014: Eliminar Módulo sin Dependencias
```
Pasos:
1. Crear un módulo sin permisos/submódulos
2. Intentar eliminarlo

Resultado Esperado:
- Eliminar exitosamente
```

### TC-015: Eliminar Módulo con Dependencias
```
Pasos:
1. Intentar eliminar módulo "Seguridad" (tiene permisos)
2. Sistema intenta eliminar

Resultado Esperado:
- Mostrar error
- No permitir eliminación
```

---

## 4️⃣ CRUD Usuario

### TC-016: Listar Usuarios
```
Pasos:
1. Ir a /security/usuario

Resultado Esperado:
- Tabla con usuarios
- Columnas: ID, Usuario, Correo, Perfil, Estado
- Paginación funciona
```

### TC-017: Crear Usuario
```
Pasos:
1. Clic "+ Crear Usuario"
2. Llenar:
   - Usuario = "juan.lopez"
   - Correo = "juan@novafarma.com"
   - Contraseña = "Test123456"
   - Perfil = "Gerente"
   - Estado = "Activo"
   - Celular = "+34666777888"
3. Clic "Guardar"

Resultado Esperado:
- Usuario creado
- Contraseña hasheada en BD
- Visible en listado
```

### TC-018: Upload de Imagen de Usuario
```
Pasos:
1. Al crear usuario, cargar imagen
2. Seleccionar archivo JPG/PNG
3. Guardar

Resultado Esperado:
- Archivo subido a public/uploads/usuarios/
- Ruta guardada en BD
- Imagen visible en detalle del usuario
```

### TC-019: Editar Usuario
```
Pasos:
1. Clic ✏️ en usuario
2. Cambiar estado: "Activo" → "Suspendido"
3. Clic "Actualizar"

Resultado Esperado:
- Estado actualizado
- Usuario no puede loguearse
```

### TC-020: Cambiar Contraseña de Usuario
```
Pasos:
1. En edición de usuario, llenar campo "Contraseña"
2. Ingresar nueva contraseña
3. Clic "Actualizar"

Resultado Esperado:
- Contraseña actualizada
- Hash guardado correctamente
- Usuario puede loguearse con nueva contraseña
```

### TC-021: Eliminar Usuario
```
Pasos:
1. Intentar eliminar usuario admin
2. Sistema rechaza

Resultado Esperado:
- Mostrar error
- Usuario admin no puede ser eliminado
```

---

## 5️⃣ CRUD Permisos-Perfil

### TC-022: Listar Permisos
```
Pasos:
1. Ir a /security/permiso-perfil

Resultado Esperado:
- Tabla con permisos
- Columnas: Módulo, Perfil, Crear, Editar, Consultar, Eliminar
- Checkmarks (✓/✗) visibles
```

### TC-023: Asignar Permisos
```
Pasos:
1. Clic "+ Crear Permiso"
2. Seleccionar:
   - Módulo = "Seguridad"
   - Perfil = "Gerente"
3. Marcar:
   - ✓ Consulta
   - ✓ Editar
   - ✗ Agregar
   - ✗ Eliminar
4. Clic "Guardar"

Resultado Esperado:
- Permiso creado
- Visible en listado
- Usuario con ese perfil tendrá esos permisos
```

### TC-024: Editar Permisos
```
Pasos:
1. Clic ✏️ en permiso
2. Cambiar: Agregar (No → Sí)
3. Clic "Actualizar"

Resultado Esperado:
- Permisos actualizados
- Cambio reflejado inmediatamente
```

### TC-025: Eliminar Permiso
```
Pasos:
1. Clic 🗑️ en un permiso

Resultado Esperado:
- Permiso eliminado
- Usuario pierde ese acceso
```

---

## 6️⃣ Funcionalidad General

### TC-026: Breadcrumbs en Todas las Vistas
```
Pasos:
1. Navegar a diferentes secciones
2. Verificar breadcrumbs en cada página

Resultado Esperado:
- Breadcrumbs presentes siempre
- Enlaces funcionales
- Última página sin enlace (actual)
```

### TC-027: Paginación Correcta
```
Pasos:
1. Ir a /security/perfil (u otro CRUD)
2. Si hay más de 5 registros, ir página 2
3. Clic en número de página

Resultado Esperado:
- Mostrar siguiente set de 5 registros
- "Primera", "Anterior", números, "Siguiente", "Última"
```

### TC-028: Validaciones de Formulario
```
Pasos:
1. Intentar guardar perfil sin nombre
2. Intentar crear usuario sin correo
3. Intentar guardar con campos vacíos

Resultado Esperado:
- Mostrar mensajes de error
- No guardar en BD
- Resaltar campos requeridos
```

### TC-029: Diseño Responsivo
```
Pasos:
1. Ver página en:
   - Escritorio (1920x1080)
   - Tablet (768x1024)
   - Móvil (375x667)
2. Verificar que se adapta correctamente

Resultado Esperado:
- Diseño se ajusta
- Botones accesibles en móvil
- Tablas scrolleables en móvil
- Sin overflow
```

### TC-030: Redirecciones Correctas
```
Pasos:
1. Intentar acceder a /security/perfil sin login
2. Sistema redirige a /auth/login
3. Después de logout, no poder acceder

Resultado Esperado:
- Redirección automática funciona
- Sesión controlada correctamente
```

---

## 7️⃣ Base de Datos

### TC-031: Integridad de Datos
```
Verificar en BD:
1. Perfiles sin usuarios adscritos: Posible eliminar
2. Perfiles con usuarios: NO posible eliminar
3. Módulos sin permisos: Posible eliminar
4. Módulos con permisos: NO posible eliminar
5. Foreign Keys funcionando
```

### TC-032: Hash de Contraseñas
```
Verificar:
1. Contraseña almacenada con hash bcrypt
2. No está en texto plano
3. password_verify() funciona
```

### TC-033: Timestamps
```
Verificar:
1. `creado_en` se llena automáticamente
2. `actualizado_en` se actualiza con cambios
3. `ultimo_login` se actualiza en cada login
```

---

## 8️⃣ Seguridad

### TC-034: Inyección SQL
```
Pasos:
1. En login, ingresar: admin' OR '1'='1
2. En formulario, ingresar: "); DROP TABLE usuario;--

Resultado Esperado:
- Treated as literal string
- No ejecutar código malicioso
- PDO prepared statements protect
```

### TC-035: XSS Prevention
```
Pasos:
1. En nombre de perfil, ingresar: <script>alert('XSS')</script>
2. Ver en listado

Resultado Esperado:
- Script no ejecutarse
- htmlspecialchars() escapar output
```

### TC-036: CSRF Protection
```
Verificar:
- Tokens CSRF en formularios (si implementado)
- POST requests validadas
```

---

## Checklist de Pruebas

- [ ] TC-001 hasta TC-036 completadas
- [ ] Todos los CRUD funcionan correctamente
- [ ] Paginación correcta (5 registros)
- [ ] Breadcrumbs presentes en todas las vistas
- [ ] Upload de imágenes funciona
- [ ] Validaciones funcionan
- [ ] Sesiones funcionan
- [ ] Base de datos intacta
- [ ] Diseño responsivo correcto
- [ ] Seguridad verificada

---

## Cómo Reportar Issues

```
Formato de reporte:
- TC: Número de caso de prueba fallido
- Descripción: Qué pasó
- Pasos: Cómo reproducir
- Resultado Esperado: Qué debería pasar
- Resultado Actual: Qué pasó realmente
- Screenshot/Log: Si es posible
```

---

## Notas de Testing

- Las pruebas se pueden ejecutar manual o automatizado
- Para automatización, usar PHPUnit o Behat
- Testing en navegadores: Chrome, Firefox, Safari
- Verificar en diferentes dispositivos

---

**Última actualización**: 10 de abril de 2026

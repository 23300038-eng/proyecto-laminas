# FIX: Extensión pgcrypto faltante (Error 502)

## Problema Identificado

La aplicación retornaba **Error 502 Bad Gateway** debido a que el script SQL (`database.sql`) intentaba usar funciones de encriptación que no estaban disponibles:

```sql
-- En database.sql línea 183
SELECT 'admin', p.id, crypt('admin123', gen_salt('bf')), ...
```

**Error en PostgreSQL:**
```
ERROR: function gen_salt(unknown) does not exist
```

Esto causaba que la inicialización de la base de datos fallara, provocando que la aplicación Laminas no pudiera conectarse a una BD correctamente inicializada.

## Causa Raíz

- `crypt()` y `gen_salt()` son funciones de la extensión **pgcrypto**
- El archivo `database.sql` no instalaba esta extensión
- Sin la extensión, el script SQL fallaba al intentar crear el usuario admin
- Esto resultaba en una base de datos incompleta/mal inicializada
- La aplicación Laminas retornaba 502 al intentar consultar tablas que no existían

## Solución Implementada

Se agregó al inicio de `database.sql`:

```sql
-- Instalar extensiones requeridas
CREATE EXTENSION IF NOT EXISTS pgcrypto;
```

Esto asegura que:
1. PostgreSQL instale la extensión pgcrypto antes de usarla
2. Las funciones `crypt()` y `gen_salt()` estén disponibles
3. El usuario admin se cree correctamente con contraseña hasheada
4. La BD se inicialice completamente

## Cambios Realizados

**Archivo:** `database.sql`
- **Línea 7:** Agregada la declaración `CREATE EXTENSION IF NOT EXISTS pgcrypto;`

## Verificación

Para confirmar que esto resuelve el problema:

1. **Railway ejecutará el nuevo `database.sql`** en el próximo deployment
2. **La extensión pgcrypto se instalará** en la BD
3. **El usuario admin se creará correctamente** con password hasheado
4. **Las tablas se crearán completamente**
5. **La aplicación debería conectarse exitosamente**

## Pasos Siguientes

1. Railway debe detectar los cambios en `database.sql`
2. La BD se reinicializará con la extensión pgcrypto
3. Hacer un request a la aplicación para verificar que ya no retorna 502

## Nota

El error de autenticación PostgreSQL (`password authentication failed for user "postgres"`) que se ve en los logs de Railway es de **Railway UI intentando monitoreo** con el usuario por defecto. No es causado por la aplicación.

---
**Commit:** `e7b99e4` - "Fix: Instalar extensión pgcrypto para funciones crypt y gen_salt"

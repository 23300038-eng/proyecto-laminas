-- ============================================================================
-- NOVAFARMA - Database Schema (PostgreSQL)
-- Sistema administrativo para farmacéutica con gestión de permisos dinámicos
-- ============================================================================

-- Tablas de soporte
CREATE TABLE IF NOT EXISTS estado_usuario (
    id SERIAL PRIMARY KEY,
    str_nombre VARCHAR(50) NOT NULL UNIQUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Perfil
-- Descripción: Define los perfiles de acceso del sistema
CREATE TABLE IF NOT EXISTS perfil (
    id SERIAL PRIMARY KEY,
    str_nombre_perfil VARCHAR(100) NOT NULL UNIQUE,
    bit_administrador BOOLEAN DEFAULT FALSE,
    descripcion TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Módulo
-- Descripción: Módulos dinámicos del sistema
CREATE TABLE IF NOT EXISTS modulo (
    id SERIAL PRIMARY KEY,
    str_nombre_modulo VARCHAR(100) NOT NULL UNIQUE,
    str_icono VARCHAR(50),
    int_orden INT DEFAULT 0,
    bit_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Submódulo
-- Descripción: Submódulos dentro de cada módulo
CREATE TABLE IF NOT EXISTS submodulo (
    id SERIAL PRIMARY KEY,
    id_modulo INT NOT NULL,
    str_nombre_submodulo VARCHAR(100) NOT NULL,
    str_ruta VARCHAR(100),
    int_orden INT DEFAULT 0,
    bit_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (id_modulo, str_nombre_submodulo),
    FOREIGN KEY (id_modulo) REFERENCES modulo(id) ON DELETE CASCADE
);

-- Tabla: Permisos-Perfil
-- Descripción: Define permisos por perfil y módulo
CREATE TABLE IF NOT EXISTS permisos_perfil (
    id SERIAL PRIMARY KEY,
    id_modulo INT NOT NULL,
    id_perfil INT NOT NULL,
    bit_agregar BOOLEAN DEFAULT FALSE,
    bit_editar BOOLEAN DEFAULT FALSE,
    bit_consulta BOOLEAN DEFAULT FALSE,
    bit_eliminar BOOLEAN DEFAULT FALSE,
    bit_detalle BOOLEAN DEFAULT FALSE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (id_modulo, id_perfil),
    FOREIGN KEY (id_modulo) REFERENCES modulo(id) ON DELETE CASCADE,
    FOREIGN KEY (id_perfil) REFERENCES perfil(id) ON DELETE CASCADE
);

-- Tabla: Usuario
-- Descripción: Usuarios del sistema
CREATE TABLE IF NOT EXISTS usuario (
    id SERIAL PRIMARY KEY,
    str_nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    id_perfil INT NOT NULL,
    str_pwd VARCHAR(255) NOT NULL,
    id_estado_usuario INT NOT NULL,
    str_correo VARCHAR(100) NOT NULL UNIQUE,
    str_numero_celular VARCHAR(20),
    imagen VARCHAR(255),
    ultimo_login TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_perfil) REFERENCES perfil(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_estado_usuario) REFERENCES estado_usuario(id) ON DELETE RESTRICT
);

-- Tabla: Menú
-- Descripción: Menú del sistema (estructura jerárquica)
CREATE TABLE IF NOT EXISTS menu (
    id SERIAL PRIMARY KEY,
    id_menu INT,
    id_submodulo INT NOT NULL,
    str_nombre_menu VARCHAR(100) NOT NULL,
    int_orden INT DEFAULT 0,
    bit_activo BOOLEAN DEFAULT TRUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_menu) REFERENCES menu(id) ON DELETE SET NULL,
    FOREIGN KEY (id_submodulo) REFERENCES submodulo(id) ON DELETE CASCADE
);

-- ============================================================================
-- ÍNDICES PARA OPTIMIZAR BÚSQUEDAS
-- ============================================================================

CREATE INDEX idx_usuario_perfil ON usuario(id_perfil);
CREATE INDEX idx_usuario_estado ON usuario(id_estado_usuario);
CREATE INDEX idx_permisos_perfil_modulo ON permisos_perfil(id_modulo);
CREATE INDEX idx_permisos_perfil_perfil ON permisos_perfil(id_perfil);
CREATE INDEX idx_submodulo_modulo ON submodulo(id_modulo);
CREATE INDEX idx_menu_modulo ON menu(id_submodulo);
CREATE INDEX idx_menu_padre ON menu(id_menu);

-- ============================================================================
-- INSERCIÓN DE DATOS INICIALES
-- ============================================================================

-- Insertar estados de usuario
INSERT INTO estado_usuario (str_nombre) VALUES 
('Activo'),
('Inactivo'),
('Suspendido')
ON CONFLICT (str_nombre) DO NOTHING;

-- Insertar perfiles iniciales
INSERT INTO perfil (str_nombre_perfil, bit_administrador, descripcion) VALUES 
('Administrador', TRUE, 'Acceso total al sistema'),
('Gerente', FALSE, 'Acceso limitado a módulos principales'),
('Empleado', FALSE, 'Acceso solo a consultas'),
('Auditor', FALSE, 'Acceso de lectura a todos los módulos')
ON CONFLICT (str_nombre_perfil) DO NOTHING;

-- Insertar módulos iniciales
INSERT INTO modulo (str_nombre_modulo, str_icono, int_orden, bit_activo) VALUES 
('Seguridad', 'lock', 1, TRUE),
('Principal 1', 'home', 2, TRUE),
('Principal 2', 'cog', 3, TRUE)
ON CONFLICT (str_nombre_modulo) DO NOTHING;

-- Insertar submódulos para Seguridad
INSERT INTO submodulo (id_modulo, str_nombre_submodulo, str_ruta, int_orden, bit_activo) VALUES 
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Seguridad'), 'Perfil', '/security/perfil', 1, TRUE),
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Seguridad'), 'Módulo', '/security/modulo', 2, TRUE),
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Seguridad'), 'Permisos-Perfil', '/security/permisos-perfil', 3, TRUE),
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Seguridad'), 'Usuario', '/security/usuario', 4, TRUE)
ON CONFLICT (id_modulo, str_nombre_submodulo) DO NOTHING;

-- Insertar submódulos para Principal 1
INSERT INTO submodulo (id_modulo, str_nombre_submodulo, str_ruta, int_orden, bit_activo) VALUES 
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Principal 1'), 'Principal 1.1', '/principal1/item1', 1, TRUE),
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Principal 1'), 'Principal 1.2', '/principal1/item2', 2, TRUE)
ON CONFLICT (id_modulo, str_nombre_submodulo) DO NOTHING;

-- Insertar submódulos para Principal 2
INSERT INTO submodulo (id_modulo, str_nombre_submodulo, str_ruta, int_orden, bit_activo) VALUES 
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Principal 2'), 'Principal 2.1', '/principal2/item1', 1, TRUE),
((SELECT id FROM modulo WHERE str_nombre_modulo = 'Principal 2'), 'Principal 2.2', '/principal2/item2', 2, TRUE)
ON CONFLICT (id_modulo, str_nombre_submodulo) DO NOTHING;

-- Insertar permisos iniciales para Administrador
INSERT INTO permisos_perfil (id_modulo, id_perfil, bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle) 
SELECT m.id, p.id, TRUE, TRUE, TRUE, TRUE, TRUE
FROM modulo m, perfil p
WHERE p.str_nombre_perfil = 'Administrador'
ON CONFLICT (id_modulo, id_perfil) DO NOTHING;

-- Insertar permisos para Gerente (limitados)
INSERT INTO permisos_perfil (id_modulo, id_perfil, bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle) 
SELECT m.id, p.id, TRUE, TRUE, TRUE, FALSE, TRUE
FROM modulo m, perfil p
WHERE p.str_nombre_perfil = 'Gerente' AND m.str_nombre_modulo IN ('Principal 1', 'Principal 2')
ON CONFLICT (id_modulo, id_perfil) DO NOTHING;

-- Insertar permisos para Empleado (solo consulta)
INSERT INTO permisos_perfil (id_modulo, id_perfil, bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle) 
SELECT m.id, p.id, FALSE, FALSE, TRUE, FALSE, TRUE
FROM modulo m, perfil p
WHERE p.str_nombre_perfil = 'Empleado' AND m.str_nombre_modulo IN ('Principal 1', 'Principal 2')
ON CONFLICT (id_modulo, id_perfil) DO NOTHING;

-- Insertar usuario administrador por defecto
INSERT INTO usuario (str_nombre_usuario, id_perfil, str_pwd, id_estado_usuario, str_correo, str_numero_celular)
SELECT 'admin', p.id, crypt('admin123', gen_salt('bf')), e.id, 'admin@novafarma.com', '+34666666666'
FROM perfil p, estado_usuario e
WHERE p.str_nombre_perfil = 'Administrador' AND e.str_nombre = 'Activo'
AND NOT EXISTS (SELECT 1 FROM usuario WHERE str_nombre_usuario = 'admin')
ON CONFLICT (str_nombre_usuario) DO NOTHING;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================

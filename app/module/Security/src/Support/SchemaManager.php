<?php

declare(strict_types=1);

namespace Security\Support;

use Laminas\Db\Adapter\AdapterInterface;

class SchemaManager
{
    public static function ensure(AdapterInterface $db): void
    {
        static $ran = false;

        if ($ran) {
            return;
        }

        $ran = true;

        $queries = [
            <<<SQL
            CREATE TABLE IF NOT EXISTS submodulo (
                id SERIAL PRIMARY KEY,
                id_modulo INT NOT NULL,
                str_nombre_submodulo VARCHAR(100) NOT NULL,
                str_ruta VARCHAR(150),
                int_orden INT DEFAULT 0,
                bit_activo BOOLEAN DEFAULT TRUE,
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (id_modulo, str_nombre_submodulo)
            )
            SQL,
            'CREATE INDEX IF NOT EXISTS idx_submodulo_modulo ON submodulo(id_modulo)',
            'ALTER TABLE permisos_perfil ADD COLUMN IF NOT EXISTS id_submodulo INT NULL',
            'ALTER TABLE permisos_perfil DROP CONSTRAINT IF EXISTS permisos_perfil_id_modulo_id_perfil_key',
            'ALTER TABLE permisos_perfil DROP CONSTRAINT IF EXISTS permisos_perfil_id_submodulo_id_perfil_key',
            'CREATE UNIQUE INDEX IF NOT EXISTS ux_permiso_perfil_modulo ON permisos_perfil(id_modulo, id_perfil) WHERE id_submodulo IS NULL',
            'CREATE UNIQUE INDEX IF NOT EXISTS ux_permiso_perfil_submodulo ON permisos_perfil(id_submodulo, id_perfil) WHERE id_submodulo IS NOT NULL',
            <<<SQL
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'fk_submodulo_modulo'
                ) THEN
                    ALTER TABLE submodulo
                    ADD CONSTRAINT fk_submodulo_modulo
                    FOREIGN KEY (id_modulo) REFERENCES modulo(id) ON DELETE CASCADE;
                END IF;
            END $$;
            SQL,
            <<<SQL
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_constraint
                    WHERE conname = 'fk_permiso_submodulo'
                ) THEN
                    ALTER TABLE permisos_perfil
                    ADD CONSTRAINT fk_permiso_submodulo
                    FOREIGN KEY (id_submodulo) REFERENCES submodulo(id) ON DELETE CASCADE;
                END IF;
            END $$;
            SQL,
            <<<SQL
            UPDATE submodulo
            SET str_ruta = '/security/permiso-perfil'
            WHERE LOWER(COALESCE(str_ruta, '')) = '/security/permisos-perfil'
            SQL,
        ];

        foreach ($queries as $query) {
            try {
                $db->query($query, []);
            } catch (\Throwable $exception) {
                // Continuar para permitir compatibilidad con bases ya existentes.
            }
        }

        self::ensureDashboardModule($db);
        self::ensureSecuritySubmodules($db);
        self::backfillLegacyModulePermissionsToSubmodules($db);
    }

    private static function ensureDashboardModule(AdapterInterface $db): void
    {
        try {
            $db->query(
                'INSERT INTO modulo (str_nombre_modulo, str_icono, int_orden, bit_activo, creado_en, actualizado_en)
                 VALUES (?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT (str_nombre_modulo)
                 DO UPDATE SET
                    str_icono = EXCLUDED.str_icono,
                    bit_activo = true,
                    actualizado_en = CURRENT_TIMESTAMP',
                ['Panel de Control', 'layout-dashboard', 0]
            );
        } catch (\Throwable $exception) {
            // Sin acción: si la tabla o restricción difiere, el sistema sigue funcionando.
        }
    }

    private static function backfillLegacyModulePermissionsToSubmodules(AdapterInterface $db): void
    {
        try {
            $existing = $db->query(
                'SELECT COUNT(*) AS total FROM permisos_perfil WHERE id_submodulo IS NOT NULL',
                []
            )->current();

            if ((int) ($existing['total'] ?? 0) > 0) {
                return;
            }

            $db->query(
                'INSERT INTO permisos_perfil (
                    id_modulo, id_submodulo, id_perfil,
                    bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle,
                    creado_en, actualizado_en
                 )
                 SELECT
                    pp.id_modulo,
                    s.id AS id_submodulo,
                    pp.id_perfil,
                    pp.bit_agregar,
                    pp.bit_editar,
                    pp.bit_consulta,
                    pp.bit_eliminar,
                    pp.bit_detalle,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                 FROM permisos_perfil pp
                 INNER JOIN submodulo s ON s.id_modulo = pp.id_modulo
                 WHERE pp.id_submodulo IS NULL
                 ON CONFLICT (id_submodulo, id_perfil) WHERE id_submodulo IS NOT NULL
                 DO NOTHING',
                []
            );
        } catch (\Throwable $exception) {
            // Compatibilidad: no detener si la base no permite algún caso concreto.
        }
    }

    private static function ensureSecuritySubmodules(AdapterInterface $db): void
    {
        try {
            $securityModule = $db->query(
                "SELECT id FROM modulo WHERE LOWER(str_nombre_modulo) = 'seguridad' LIMIT 1",
                []
            )->current();

            if (!$securityModule) {
                return;
            }

            $moduleId = (int) $securityModule['id'];
            $rows = [
                ['Perfil', '/security/perfil', 1],
                ['Módulo', '/security/modulo', 2],
                ['Submódulo', '/security/submodulo', 3],
                ['Permisos-Perfil', '/security/permiso-perfil', 4],
                ['Usuario', '/security/usuario', 5],
            ];

            foreach ($rows as [$name, $route, $order]) {
                $db->query(
                    'INSERT INTO submodulo (id_modulo, str_nombre_submodulo, str_ruta, int_orden, bit_activo, creado_en, actualizado_en)
                     VALUES (?, ?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                     ON CONFLICT (id_modulo, str_nombre_submodulo)
                     DO UPDATE SET
                        str_ruta = EXCLUDED.str_ruta,
                        int_orden = EXCLUDED.int_orden,
                        bit_activo = true,
                        actualizado_en = CURRENT_TIMESTAMP',
                    [$moduleId, $name, $route, $order]
                );
            }
        } catch (\Throwable $exception) {
            // Sin acción: el sistema seguirá funcionando con lo disponible.
        }
    }
}

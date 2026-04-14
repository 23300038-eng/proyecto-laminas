<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Security\Support\AccessHelper;

class PermisoPerfilModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function getPermisos(int $limit = 20, int $offset = 0): array
    {
        try {
            $result = $this->db->query(
                'SELECT 
                    pp.id,
                    pp.id_modulo,
                    pp.id_submodulo,
                    pp.id_perfil,
                    pp.bit_agregar,
                    pp.bit_editar,
                    pp.bit_consulta,
                    pp.bit_eliminar,
                    pp.bit_detalle,
                    p.str_nombre_perfil,
                    m.str_nombre_modulo,
                    s.str_nombre_submodulo,
                    COALESCE(s.str_nombre_submodulo, m.str_nombre_modulo) AS str_recurso
                 FROM permisos_perfil pp
                 INNER JOIN perfil p ON p.id = pp.id_perfil
                 INNER JOIN modulo m ON m.id = pp.id_modulo
                 LEFT JOIN submodulo s ON s.id = pp.id_submodulo
                 ORDER BY p.str_nombre_perfil ASC, m.int_orden ASC, COALESCE(s.int_orden, 0) ASC, str_recurso ASC
                 LIMIT ? OFFSET ?',
                [$limit, $offset]
            );

            return array_values(iterator_to_array($result));
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function getPermisosTotal(): int
    {
        $sql = new Sql($this->db);
        $select = $sql->select('permisos_perfil');
        $select->columns([new Expression('COUNT(*) as total')]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        return (int) ($row['total'] ?? 0);
    }

    public function getPermiso(int $id): ?array
    {
        try {
            $row = $this->db->query(
                'SELECT 
                    pp.*, 
                    p.str_nombre_perfil,
                    m.str_nombre_modulo,
                    s.str_nombre_submodulo,
                    COALESCE(s.str_nombre_submodulo, m.str_nombre_modulo) AS str_recurso
                 FROM permisos_perfil pp
                 INNER JOIN perfil p ON p.id = pp.id_perfil
                 INNER JOIN modulo m ON m.id = pp.id_modulo
                 LEFT JOIN submodulo s ON s.id = pp.id_submodulo
                 WHERE pp.id = ?
                 LIMIT 1',
                [$id]
            )->current();

            return $row ? (array) $row : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function createPermiso(array $data): int
    {
        $payload = [
            'id_modulo' => (int) ($data['id_modulo'] ?? 0),
            'id_submodulo' => !empty($data['id_submodulo']) ? (int) $data['id_submodulo'] : null,
            'id_perfil' => (int) ($data['id_perfil'] ?? 0),
            'bit_agregar' => !empty($data['bit_agregar']),
            'bit_editar' => !empty($data['bit_editar']),
            'bit_consulta' => !empty($data['bit_consulta']),
            'bit_eliminar' => !empty($data['bit_eliminar']),
            'bit_detalle' => !empty($data['bit_detalle']),
        ];

        if ($payload['id_submodulo']) {
            $this->db->query(
                'INSERT INTO permisos_perfil (
                    id_modulo, id_submodulo, id_perfil,
                    bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle,
                    creado_en, actualizado_en
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT (id_submodulo, id_perfil) WHERE id_submodulo IS NOT NULL
                 DO UPDATE SET
                    id_modulo = EXCLUDED.id_modulo,
                    bit_agregar = EXCLUDED.bit_agregar,
                    bit_editar = EXCLUDED.bit_editar,
                    bit_consulta = EXCLUDED.bit_consulta,
                    bit_eliminar = EXCLUDED.bit_eliminar,
                    bit_detalle = EXCLUDED.bit_detalle,
                    actualizado_en = CURRENT_TIMESTAMP',
                [
                    $payload['id_modulo'],
                    $payload['id_submodulo'],
                    $payload['id_perfil'],
                    $payload['bit_agregar'],
                    $payload['bit_editar'],
                    $payload['bit_consulta'],
                    $payload['bit_eliminar'],
                    $payload['bit_detalle'],
                ]
            );
        } else {
            $this->db->query(
                'INSERT INTO permisos_perfil (
                    id_modulo, id_submodulo, id_perfil,
                    bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle,
                    creado_en, actualizado_en
                 ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT (id_modulo, id_perfil) WHERE id_submodulo IS NULL
                 DO UPDATE SET
                    bit_agregar = EXCLUDED.bit_agregar,
                    bit_editar = EXCLUDED.bit_editar,
                    bit_consulta = EXCLUDED.bit_consulta,
                    bit_eliminar = EXCLUDED.bit_eliminar,
                    bit_detalle = EXCLUDED.bit_detalle,
                    actualizado_en = CURRENT_TIMESTAMP',
                [
                    $payload['id_modulo'],
                    $payload['id_perfil'],
                    $payload['bit_agregar'],
                    $payload['bit_editar'],
                    $payload['bit_consulta'],
                    $payload['bit_eliminar'],
                    $payload['bit_detalle'],
                ]
            );
        }

        return (int) $this->db->getDriver()->getLastGeneratedValue();
    }

    public function updatePermiso(int $id, array $data): bool
    {
        $sql = new Sql($this->db);
        $update = $sql->update('permisos_perfil')
            ->set([
                'bit_agregar' => !empty($data['bit_agregar']),
                'bit_editar' => !empty($data['bit_editar']),
                'bit_consulta' => !empty($data['bit_consulta']),
                'bit_eliminar' => !empty($data['bit_eliminar']),
                'bit_detalle' => !empty($data['bit_detalle']),
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $result = $sql->prepareStatementForSqlObject($update)->execute();
        return $result->getAffectedRows() > 0;
    }

    public function deletePermiso(int $id): bool
    {
        $sql = new Sql($this->db);
        $delete = $sql->delete('permisos_perfil')->where(['id' => $id]);
        $result = $sql->prepareStatementForSqlObject($delete)->execute();
        return $result->getAffectedRows() > 0;
    }

    public function getPermisosByPerfil(int $idPerfil): array
    {
        try {
            $result = $this->db->query(
                'SELECT * FROM permisos_perfil WHERE id_perfil = ? ORDER BY id_modulo ASC, COALESCE(id_submodulo, 0) ASC',
                [$idPerfil]
            );

            return array_values(iterator_to_array($result));
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function getPermisosIndexadosByPerfil(int $idPerfil): array
    {
        $indexed = [
            'modulos' => [],
            'submodulos' => [],
        ];

        foreach ($this->getPermisosByPerfil($idPerfil) as $row) {
            $bits = AccessHelper::normalizeBits((array) $row);

            if (!empty($row['id_submodulo'])) {
                $indexed['submodulos'][(int) $row['id_submodulo']] = $bits;
            } else {
                $indexed['modulos'][(int) $row['id_modulo']] = $bits;
            }
        }

        return $indexed;
    }

    public function getSessionPermissionsForPerfil(int $idPerfil): array
    {
        $indexed = $this->getPermisosIndexadosByPerfil($idPerfil);

        return [
            'modulos' => $indexed['modulos'],
            'submodulos' => $indexed['submodulos'],
        ];
    }

    public function savePermisosByPerfil(int $idPerfil, array $permisos): void
    {
        $matrix = $this->normalizeInputPermisos($permisos);
        $modulos = $this->getModulosActivosConSubmodulos();

        foreach ($modulos as $modulo) {
            $moduleId = (int) $modulo['id'];
            $moduleBits = AccessHelper::normalizeBits($matrix['modulos'][$moduleId] ?? []);
            $this->persistPermissionRow($idPerfil, $moduleId, null, $moduleBits);

            foreach ($modulo['submodulos'] as $submodulo) {
                $submoduleId = (int) $submodulo['id'];
                $subBits = AccessHelper::normalizeBits($matrix['submodulos'][$submoduleId] ?? []);
                $this->persistPermissionRow($idPerfil, $moduleId, $submoduleId, $subBits);
            }
        }
    }

    public function getModulosForSelect(): array
    {
        $result = [];
        foreach ($this->getModulosActivos() as $row) {
            $result[$row['id']] = $row['str_nombre_modulo'];
        }
        return $result;
    }

    public function getPerfilesForSelect(): array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('perfil')->order('str_nombre_perfil ASC');
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        $perfiles = [];
        foreach ($result as $row) {
            $perfiles[$row['id']] = $row['str_nombre_perfil'];
        }

        return $perfiles;
    }

    public function getModulosActivos(): array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('modulo')
            ->where(['bit_activo' => true])
            ->order('int_orden ASC');
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        return array_values(iterator_to_array($result));
    }

    public function getModulosActivosConSubmodulos(): array
    {
        try {
            $modules = [];
            $moduleRows = $this->db->query(
                'SELECT id, str_nombre_modulo, str_icono, int_orden, bit_activo
                 FROM modulo
                 WHERE COALESCE(bit_activo, true) = true
                 ORDER BY int_orden ASC, str_nombre_modulo ASC',
                []
            );

            foreach ($moduleRows as $row) {
                $row = (array) $row;
                $row['submodulos'] = [];
                $modules[(int) $row['id']] = $row;
            }

            $subRows = $this->db->query(
                'SELECT id, id_modulo, str_nombre_submodulo, str_ruta, int_orden, bit_activo
                 FROM submodulo
                 WHERE COALESCE(bit_activo, true) = true
                 ORDER BY int_orden ASC, str_nombre_submodulo ASC',
                []
            );

            foreach ($subRows as $row) {
                $moduleId = (int) $row['id_modulo'];
                if (isset($modules[$moduleId])) {
                    $modules[$moduleId]['submodulos'][] = (array) $row;
                }
            }

            return array_values($modules);
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function profileIsAdmin(int $idPerfil): bool
    {
        try {
            $perfil = $this->db->query('SELECT bit_administrador FROM perfil WHERE id = ? LIMIT 1', [$idPerfil])->current();
            if (!empty($perfil['bit_administrador'])) {
                return true;
            }
        } catch (\Throwable $exception) {
            // continuar con evaluación por permisos.
        }

        $securityModuleId = AccessHelper::getModuleIdByName($this->db, 'Seguridad');
        if ($securityModuleId === null) {
            return false;
        }

        $permissions = $this->getSessionPermissionsForPerfil($idPerfil);
        $moduleBits = AccessHelper::normalizeBits($permissions['modulos'][$securityModuleId] ?? []);
        if (!AccessHelper::hasAnyPermission($moduleBits)) {
            return false;
        }

        $requiredNames = ['Perfil', 'Módulo', 'Submódulo', 'Permisos-Perfil', 'Usuario'];

        try {
            $submodules = $this->db->query(
                'SELECT id, str_nombre_submodulo
                 FROM submodulo
                 WHERE id_modulo = ? AND COALESCE(bit_activo, true) = true',
                [$securityModuleId]
            );

            $map = [];
            foreach ($submodules as $submodule) {
                $map[AccessHelper::slugify((string) $submodule['str_nombre_submodulo'])] = (int) $submodule['id'];
            }

            foreach ($requiredNames as $name) {
                $subId = $map[AccessHelper::slugify($name)] ?? null;
                if ($subId === null) {
                    return false;
                }

                $bits = AccessHelper::normalizeBits($permissions['submodulos'][$subId] ?? []);
                if (!AccessHelper::hasAnyPermission($bits)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function normalizeInputPermisos(array $permisos): array
    {
        if (isset($permisos['modulos']) || isset($permisos['submodulos'])) {
            return [
                'modulos' => is_array($permisos['modulos'] ?? null) ? $permisos['modulos'] : [],
                'submodulos' => is_array($permisos['submodulos'] ?? null) ? $permisos['submodulos'] : [],
            ];
        }

        return [
            'modulos' => $permisos,
            'submodulos' => [],
        ];
    }

    private function persistPermissionRow(int $idPerfil, int $idModulo, ?int $idSubmodulo, array $bits): void
    {
        $bits = AccessHelper::normalizeBits($bits);
        $hasAny = AccessHelper::hasAnyPermission($bits);

        if ($idSubmodulo !== null) {
            if ($hasAny) {
                $this->db->query(
                    'INSERT INTO permisos_perfil (
                        id_modulo, id_submodulo, id_perfil,
                        bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle,
                        creado_en, actualizado_en
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                     ON CONFLICT (id_submodulo, id_perfil) WHERE id_submodulo IS NOT NULL
                     DO UPDATE SET
                        id_modulo = EXCLUDED.id_modulo,
                        bit_agregar = EXCLUDED.bit_agregar,
                        bit_editar = EXCLUDED.bit_editar,
                        bit_consulta = EXCLUDED.bit_consulta,
                        bit_eliminar = EXCLUDED.bit_eliminar,
                        bit_detalle = EXCLUDED.bit_detalle,
                        actualizado_en = CURRENT_TIMESTAMP',
                    [$idModulo, $idSubmodulo, $idPerfil, $bits['agregar'], $bits['editar'], $bits['consulta'], $bits['eliminar'], $bits['detalle']]
                );
            } else {
                $this->db->query(
                    'DELETE FROM permisos_perfil WHERE id_perfil = ? AND id_submodulo = ?',
                    [$idPerfil, $idSubmodulo]
                );
            }
            return;
        }

        if ($hasAny) {
            $this->db->query(
                'INSERT INTO permisos_perfil (
                    id_modulo, id_submodulo, id_perfil,
                    bit_agregar, bit_editar, bit_consulta, bit_eliminar, bit_detalle,
                    creado_en, actualizado_en
                 ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT (id_modulo, id_perfil) WHERE id_submodulo IS NULL
                 DO UPDATE SET
                    bit_agregar = EXCLUDED.bit_agregar,
                    bit_editar = EXCLUDED.bit_editar,
                    bit_consulta = EXCLUDED.bit_consulta,
                    bit_eliminar = EXCLUDED.bit_eliminar,
                    bit_detalle = EXCLUDED.bit_detalle,
                    actualizado_en = CURRENT_TIMESTAMP',
                [$idModulo, $idPerfil, $bits['agregar'], $bits['editar'], $bits['consulta'], $bits['eliminar'], $bits['detalle']]
            );
        } else {
            $this->db->query(
                'DELETE FROM permisos_perfil WHERE id_perfil = ? AND id_modulo = ? AND id_submodulo IS NULL',
                [$idPerfil, $idModulo]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;
use Security\Support\AccessHelper;

class SubmoduloModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function getSubmodulos(int $limit = 10, int $offset = 0, ?int $moduleId = null): array
    {
        try {
            $params = [];
            $where = '';
            if ($moduleId !== null && $moduleId > 0) {
                $where = 'WHERE s.id_modulo = ?';
                $params[] = $moduleId;
            }
            $params[] = $limit;
            $params[] = $offset;

            $result = $this->db->query(
                "SELECT 
                    s.id,
                    s.id_modulo,
                    s.str_nombre_submodulo,
                    s.str_ruta,
                    s.int_orden,
                    s.bit_activo,
                    m.str_nombre_modulo
                 FROM submodulo s
                 INNER JOIN modulo m ON m.id = s.id_modulo
                 {$where}
                 ORDER BY m.int_orden ASC, s.int_orden ASC, s.str_nombre_submodulo ASC
                 LIMIT ? OFFSET ?",
                $params
            );

            return array_values(iterator_to_array($result));
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function getSubmodulosTotal(?int $moduleId = null): int
    {
        try {
            if ($moduleId !== null && $moduleId > 0) {
                $row = $this->db->query('SELECT COUNT(*) AS total FROM submodulo WHERE id_modulo = ?', [$moduleId])->current();
            } else {
                $row = $this->db->query('SELECT COUNT(*) AS total FROM submodulo', [])->current();
            }

            return (int) ($row['total'] ?? 0);
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    public function getSubmodulo(int $id): ?array
    {
        try {
            $row = $this->db->query(
                'SELECT s.*, m.str_nombre_modulo
                 FROM submodulo s
                 INNER JOIN modulo m ON m.id = s.id_modulo
                 WHERE s.id = ?
                 LIMIT 1',
                [$id]
            )->current();

            return $row ? (array) $row : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function getSubmoduloBySlug(int $moduleId, string $slug): ?array
    {
        try {
            $rows = $this->db->query(
                'SELECT s.*, m.str_nombre_modulo
                 FROM submodulo s
                 INNER JOIN modulo m ON m.id = s.id_modulo
                 WHERE s.id_modulo = ? AND COALESCE(s.bit_activo, true) = true
                 ORDER BY s.int_orden ASC',
                [$moduleId]
            );

            foreach ($rows as $row) {
                if (AccessHelper::slugify((string) $row['str_nombre_submodulo']) === $slug) {
                    return (array) $row;
                }
            }

            return null;
        } catch (\Throwable $exception) {
            return null;
        }
    }


    public function getSubmoduloByRoute(string $path): ?array
    {
        $normalizedPath = '/' . ltrim(trim($path), '/');
        $normalizedPath = rtrim($normalizedPath, '/');
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        try {
            $rows = $this->db->query(
                'SELECT s.*, m.str_nombre_modulo
                 FROM submodulo s
                 INNER JOIN modulo m ON m.id = s.id_modulo
                 WHERE COALESCE(s.bit_activo, true) = true
                   AND COALESCE(m.bit_activo, true) = true
                 ORDER BY m.int_orden ASC, s.int_orden ASC, s.str_nombre_submodulo ASC',
                []
            );

            foreach ($rows as $row) {
                $storedRoute = '/' . ltrim((string) ($row['str_ruta'] ?? ''), '/');
                $storedRoute = rtrim($storedRoute, '/');
                if ($storedRoute === '') {
                    continue;
                }

                if ($storedRoute === $normalizedPath) {
                    return (array) $row;
                }
            }

            return null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function getSubmodulosActivosByModulo(int $moduleId): array
    {
        try {
            $result = $this->db->query(
                'SELECT s.*, m.str_nombre_modulo
                 FROM submodulo s
                 INNER JOIN modulo m ON m.id = s.id_modulo
                 WHERE s.id_modulo = ? AND COALESCE(s.bit_activo, true) = true
                 ORDER BY s.int_orden ASC, s.str_nombre_submodulo ASC',
                [$moduleId]
            );

            return array_values(iterator_to_array($result));
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function createSubmodulo(array $data): int
    {
        $moduleId = (int) ($data['id_modulo'] ?? 0);
        $moduleName = $this->getModuleName($moduleId);
        $name = trim((string) ($data['str_nombre_submodulo'] ?? ''));
        $route = trim((string) ($data['str_ruta'] ?? ''));

        if ($route === '' && $moduleName !== null) {
            $route = AccessHelper::normalizeSubmoduleRoute($moduleName, $name, null);
        }

        $sql = new Sql($this->db);
        $insert = $sql->insert('submodulo')
            ->values([
                'id_modulo' => $moduleId,
                'str_nombre_submodulo' => $name,
                'str_ruta' => $route !== '' ? $route : null,
                'int_orden' => (int) ($data['int_orden'] ?? 0),
                'bit_activo' => !empty($data['bit_activo']),
            ]);

        $sql->prepareStatementForSqlObject($insert)->execute();
        return (int) $this->db->getDriver()->getLastGeneratedValue();
    }

    public function updateSubmodulo(int $id, array $data): bool
    {
        $moduleId = (int) ($data['id_modulo'] ?? 0);
        $moduleName = $this->getModuleName($moduleId);
        $name = trim((string) ($data['str_nombre_submodulo'] ?? ''));
        $route = trim((string) ($data['str_ruta'] ?? ''));

        if ($route === '' && $moduleName !== null) {
            $route = AccessHelper::normalizeSubmoduleRoute($moduleName, $name, null);
        }

        $sql = new Sql($this->db);
        $update = $sql->update('submodulo')
            ->set([
                'id_modulo' => $moduleId,
                'str_nombre_submodulo' => $name,
                'str_ruta' => $route !== '' ? $route : null,
                'int_orden' => (int) ($data['int_orden'] ?? 0),
                'bit_activo' => !empty($data['bit_activo']),
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $result = $sql->prepareStatementForSqlObject($update)->execute();
        return $result->getAffectedRows() > 0;
    }

    public function deleteSubmodulo(int $id): bool
    {
        try {
            $row = $this->db->query('SELECT COUNT(*) AS total FROM permisos_perfil WHERE id_submodulo = ?', [$id])->current();
            if ((int) ($row['total'] ?? 0) > 0) {
                return false;
            }
        } catch (\Throwable $exception) {
            return false;
        }

        $sql = new Sql($this->db);
        $delete = $sql->delete('submodulo')->where(['id' => $id]);
        $result = $sql->prepareStatementForSqlObject($delete)->execute();
        return $result->getAffectedRows() > 0;
    }

    public function getModulosForSelect(): array
    {
        $result = [];
        try {
            $rows = $this->db->query(
                'SELECT id, str_nombre_modulo FROM modulo WHERE COALESCE(bit_activo, true) = true ORDER BY int_orden ASC, str_nombre_modulo ASC',
                []
            );

            foreach ($rows as $row) {
                $result[$row['id']] = $row['str_nombre_modulo'];
            }
        } catch (\Throwable $exception) {
            return [];
        }

        return $result;
    }

    private function getModuleName(int $moduleId): ?string
    {
        try {
            $row = $this->db->query('SELECT str_nombre_modulo FROM modulo WHERE id = ? LIMIT 1', [$moduleId])->current();
            return $row ? (string) $row['str_nombre_modulo'] : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

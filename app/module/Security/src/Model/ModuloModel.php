<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;

class ModuloModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function getModulos(int $limit = 5, int $offset = 0): array
    {
        try {
            $result = $this->db->query(
                'SELECT 
                    m.id,
                    m.str_nombre_modulo,
                    m.str_icono,
                    m.int_orden,
                    m.bit_activo,
                    COUNT(s.id) AS total_submodulos
                 FROM modulo m
                 LEFT JOIN submodulo s ON s.id_modulo = m.id
                 GROUP BY m.id, m.str_nombre_modulo, m.str_icono, m.int_orden, m.bit_activo
                 ORDER BY m.int_orden ASC, m.str_nombre_modulo ASC
                 LIMIT ? OFFSET ?',
                [$limit, $offset]
            );

            return array_values(iterator_to_array($result));
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public function getModulosTotal(): int
    {
        $sql = new Sql($this->db);
        $select = $sql->select('modulo');
        $select->columns([new Expression('COUNT(*) as total')]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        return (int) ($row['total'] ?? 0);
    }

    public function getModulo(int $id): ?array
    {
        try {
            $row = $this->db->query(
                'SELECT 
                    m.*, 
                    COUNT(s.id) AS total_submodulos
                 FROM modulo m
                 LEFT JOIN submodulo s ON s.id_modulo = m.id
                 WHERE m.id = ?
                 GROUP BY m.id',
                [$id]
            )->current();

            return $row ? (array) $row : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function getModuloBySlug(string $slug): ?array
    {
        try {
            $rows = $this->db->query(
                'SELECT * FROM modulo WHERE COALESCE(bit_activo, true) = true ORDER BY int_orden ASC',
                []
            );

            foreach ($rows as $row) {
                if (\Security\Support\AccessHelper::slugify((string) $row['str_nombre_modulo']) === $slug) {
                    return (array) $row;
                }
            }

            return null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function createModulo(array $data): int
    {
        $sql = new Sql($this->db);
        $insert = $sql->insert('modulo')
            ->values([
                'str_nombre_modulo' => trim((string) ($data['str_nombre_modulo'] ?? '')),
                'str_icono' => trim((string) ($data['str_icono'] ?? '')) ?: null,
                'int_orden' => (int) ($data['int_orden'] ?? 0),
                'bit_activo' => !empty($data['bit_activo']),
            ]);

        $sql->prepareStatementForSqlObject($insert)->execute();
        return (int) $this->db->getDriver()->getLastGeneratedValue();
    }

    public function updateModulo(int $id, array $data): bool
    {
        $sql = new Sql($this->db);
        $update = $sql->update('modulo')
            ->set([
                'str_nombre_modulo' => trim((string) ($data['str_nombre_modulo'] ?? '')),
                'str_icono' => trim((string) ($data['str_icono'] ?? '')) ?: null,
                'int_orden' => (int) ($data['int_orden'] ?? 0),
                'bit_activo' => !empty($data['bit_activo']),
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $result = $sql->prepareStatementForSqlObject($update)->execute();
        return $result->getAffectedRows() > 0;
    }

    public function deleteModulo(int $id): bool
    {
        try {
            $row = $this->db->query('SELECT COUNT(*) AS total FROM permisos_perfil WHERE id_modulo = ?', [$id])->current();
            if ((int) ($row['total'] ?? 0) > 0) {
                return false;
            }

            $row = $this->db->query('SELECT COUNT(*) AS total FROM submodulo WHERE id_modulo = ?', [$id])->current();
            if ((int) ($row['total'] ?? 0) > 0) {
                return false;
            }
        } catch (\Throwable $exception) {
            return false;
        }

        $sql = new Sql($this->db);
        $delete = $sql->delete('modulo')->where(['id' => $id]);
        $result = $sql->prepareStatementForSqlObject($delete)->execute();
        return $result->getAffectedRows() > 0;
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
}

<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class ModuloModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los módulos con paginación
     */
    public function getModulos(int $limit = 5, int $offset = 0): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('modulo')
            ->order('int_orden ASC')
            ->limit($limit)
            ->offset($offset);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return array_values(iterator_to_array($result));
    }

    /**
     * Obtener total de módulos
     */
    public function getModulosTotal(): int
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('modulo');
        $select->columns([new Expression('COUNT(*) as total')]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return (int)$row['total'];
    }

    /**
     * Obtener módulo por ID
     */
    public function getModulo(int $id): ?array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('modulo')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return $row ? (array)$row : null;
    }

    /**
     * Crear nuevo módulo
     */
    public function createModulo(array $data): int
    {
        $sql = new Sql($this->db);
        
        $insert = $sql->insert('modulo')
            ->values([
                'str_nombre_modulo' => $data['str_nombre_modulo'] ?? null,
                'str_icono' => $data['str_icono'] ?? null,
                'int_orden' => $data['int_orden'] ?? 0,
                'bit_activo' => $data['bit_activo'] ?? true,
            ]);

        $stmt = $sql->prepareStatementForSqlObject($insert);
        $result = $stmt->execute();

        return $this->db->getDriver()->getLastGeneratedValue();
    }

    /**
     * Actualizar módulo
     */
    public function updateModulo(int $id, array $data): bool
    {
        $sql = new Sql($this->db);
        
        $update = $sql->update('modulo')
            ->set([
                'str_nombre_modulo' => $data['str_nombre_modulo'] ?? null,
                'str_icono' => $data['str_icono'] ?? null,
                'int_orden' => $data['int_orden'] ?? 0,
                'bit_activo' => $data['bit_activo'] ?? true,
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($update);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Eliminar módulo
     */
    public function deleteModulo(int $id): bool
    {
        $sql = new Sql($this->db);
        
        // No permitir eliminar si hay permisos o submódulos asociados
        $selectCount = $sql->select('permisos_perfil')
            ->columns([new Expression('COUNT(*) as total')])
            ->where(['id_modulo' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($selectCount);
        $result = $stmt->execute();
        $row = $result->current();

        if ((int)$row['total'] > 0) {
            return false;
        }

        $selectCount = $sql->select('submodulo')
            ->columns([new Expression('COUNT(*) as total')])
            ->where(['id_modulo' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($selectCount);
        $result = $stmt->execute();
        $row = $result->current();

        if ((int)$row['total'] > 0) {
            return false;
        }

        $delete = $sql->delete('modulo')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($delete);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Obtener todos los módulos activos (sin paginación)
     */
    public function getModulosActivos(): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('modulo')
            ->where(['bit_activo' => true])
            ->order('int_orden ASC');

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return array_values(iterator_to_array($result));
    }
}

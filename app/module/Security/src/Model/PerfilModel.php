<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class PerfilModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los perfiles con paginación
     */
    public function getPerfiles(int $limit = 5, int $offset = 0): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('perfil')
            ->order('creado_en DESC')
            ->limit($limit)
            ->offset($offset);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return array_values(iterator_to_array($result));
    }

    /**
     * Obtener total de perfiles
     */
    public function getPerfilesTotal(): int
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('perfil');
        $select->columns([new Expression('COUNT(*) as total')]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return (int)$row['total'];
    }

    /**
     * Obtener perfil por ID
     */
    public function getPerfil(int $id): ?array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('perfil')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return $row ? (array)$row : null;
    }

    /**
     * Crear nuevo perfil
     */
    public function createPerfil(array $data): int
    {
        $sql = new Sql($this->db);
        
        $insert = $sql->insert('perfil')
            ->values([
                'str_nombre_perfil' => $data['str_nombre_perfil'] ?? null,
                'bit_administrador' => $data['bit_administrador'] ?? false,
                'descripcion' => $data['descripcion'] ?? null,
            ]);

        $stmt = $sql->prepareStatementForSqlObject($insert);
        $result = $stmt->execute();

        return $this->db->getDriver()->getLastGeneratedValue();
    }

    /**
     * Actualizar perfil
     */
    public function updatePerfil(int $id, array $data): bool
    {
        $sql = new Sql($this->db);
        
        $update = $sql->update('perfil')
            ->set([
                'str_nombre_perfil' => $data['str_nombre_perfil'] ?? null,
                'bit_administrador' => $data['bit_administrador'] ?? false,
                'descripcion' => $data['descripcion'] ?? null,
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($update);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Eliminar perfil
     */
    public function deletePerfil(int $id): bool
    {
        $sql = new Sql($this->db);
        
        // No permitir eliminar si hay usuarios asignados
        $selectCount = $sql->select('usuario')
            ->columns([new Expression('COUNT(*) as total')])
            ->where(['id_perfil' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($selectCount);
        $result = $stmt->execute();
        $row = $result->current();

        if ((int)$row['total'] > 0) {
            return false; // No se puede eliminar si hay usuarios
        }

        $delete = $sql->delete('perfil')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($delete);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }
}

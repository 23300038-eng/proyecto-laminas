<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class PermisoPerfilModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los permisos
     */
    public function getPermisos(int $limit = 5, int $offset = 0): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('permisos_perfil')
            ->join('modulo', 'permisos_perfil.id_modulo = modulo.id', ['str_nombre_modulo'])
            ->join('perfil', 'permisos_perfil.id_perfil = perfil.id', ['str_nombre_perfil'])
            ->order('perfil.str_nombre_perfil ASC, modulo.str_nombre_modulo ASC')
            ->limit($limit)
            ->offset($offset);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return array_values(iterator_to_array($result));
    }

    /**
     * Obtener total de permisos
     */
    public function getPermisosTotal(): int
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('permisos_perfil');
        $select->columns([new Expression('COUNT(*) as total')]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return (int)$row['total'];
    }

    /**
     * Obtener permiso por ID
     */
    public function getPermiso(int $id): ?array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('permisos_perfil')
            ->join('modulo', 'permisos_perfil.id_modulo = modulo.id', ['str_nombre_modulo'])
            ->join('perfil', 'permisos_perfil.id_perfil = perfil.id', ['str_nombre_perfil'])
            ->where(['permisos_perfil.id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return $row ? (array)$row : null;
    }

    /**
     * Crear nuevo permiso
     */
    public function createPermiso(array $data): int
    {
        $sql = new Sql($this->db);
        
        $insert = $sql->insert('permisos_perfil')
            ->values([
                'id_modulo' => $data['id_modulo'] ?? null,
                'id_perfil' => $data['id_perfil'] ?? null,
                'bit_agregar' => $data['bit_agregar'] ?? false,
                'bit_editar' => $data['bit_editar'] ?? false,
                'bit_consulta' => $data['bit_consulta'] ?? false,
                'bit_eliminar' => $data['bit_eliminar'] ?? false,
                'bit_detalle' => $data['bit_detalle'] ?? false,
            ]);

        $stmt = $sql->prepareStatementForSqlObject($insert);
        $result = $stmt->execute();

        return $this->db->getDriver()->getLastGeneratedValue();
    }

    /**
     * Actualizar permiso
     */
    public function updatePermiso(int $id, array $data): bool
    {
        $sql = new Sql($this->db);
        
        $update = $sql->update('permisos_perfil')
            ->set([
                'bit_agregar' => $data['bit_agregar'] ?? false,
                'bit_editar' => $data['bit_editar'] ?? false,
                'bit_consulta' => $data['bit_consulta'] ?? false,
                'bit_eliminar' => $data['bit_eliminar'] ?? false,
                'bit_detalle' => $data['bit_detalle'] ?? false,
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($update);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Eliminar permiso
     */
    public function deletePermiso(int $id): bool
    {
        $sql = new Sql($this->db);
        
        $delete = $sql->delete('permisos_perfil')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($delete);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Obtener permisos por perfil
     */
    public function getPermisosByPerfil(int $idPerfil): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('permisos_perfil')
            ->where(['id_perfil' => $idPerfil]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return array_values(iterator_to_array($result));
    }

    /**
     * Obtener lista de módulos y perfiles para seleccionar
     */
    public function getModulosForSelect(): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('modulo')
            ->order('str_nombre_modulo ASC');

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        $modulos = [];
        foreach ($result as $row) {
            $modulos[$row['id']] = $row['str_nombre_modulo'];
        }

        return $modulos;
    }

    /**
     * Obtener lista de perfiles para seleccionar
     */
    public function getPerfilesForSelect(): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('perfil')
            ->order('str_nombre_perfil ASC');

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        $perfiles = [];
        foreach ($result as $row) {
            $perfiles[$row['id']] = $row['str_nombre_perfil'];
        }

        return $perfiles;
    }
}

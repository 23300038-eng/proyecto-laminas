<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;

class UsuarioModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener todos los usuarios con paginación
     */
    public function getUsuarios(int $limit = 5, int $offset = 0): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('usuario')
            ->join('perfil', 'usuario.id_perfil = perfil.id', ['str_nombre_perfil'])
            ->join('estado_usuario', 'usuario.id_estado_usuario = estado_usuario.id', ['str_nombre' => 'str_nombre'], 'left')
            ->order('usuario.creado_en DESC')
            ->limit($limit)
            ->offset($offset);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        return array_values(iterator_to_array($result));
    }

    /**
     * Obtener total de usuarios
     */
    public function getUsuariosTotal(): int
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('usuario');
        $select->columns([new Expression('COUNT(*) as total')]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return (int)$row['total'];
    }

    /**
     * Obtener usuario por ID
     */
    public function getUsuario(int $id): ?array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('usuario')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return $row ? (array)$row : null;
    }

    /**
     * Obtener usuario por nombre
     */
    public function getUsuarioByUsername(string $username): ?array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('usuario')
            ->where(['str_nombre_usuario' => $username]);

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();
        $row = $result->current();

        return $row ? (array)$row : null;
    }

    /**
     * Crear nuevo usuario
     */
    public function createUsuario(array $data): int
    {
        $sql = new Sql($this->db);
        
        // Hash de la contraseña
        $hashedPassword = password_hash($data['str_pwd'] ?? '', PASSWORD_BCRYPT);
        
        $insert = $sql->insert('usuario')
            ->values([
                'str_nombre_usuario' => $data['str_nombre_usuario'] ?? null,
                'id_perfil' => $data['id_perfil'] ?? null,
                'str_pwd' => $hashedPassword,
                'id_estado_usuario' => $data['id_estado_usuario'] ?? 1,
                'str_correo' => $data['str_correo'] ?? null,
                'str_numero_celular' => $data['str_numero_celular'] ?? null,
                'imagen' => $data['imagen'] ?? null,
            ]);

        $stmt = $sql->prepareStatementForSqlObject($insert);
        $result = $stmt->execute();

        return $this->db->getDriver()->getLastGeneratedValue();
    }

    /**
     * Actualizar usuario
     */
    public function updateUsuario(int $id, array $data): bool
    {
        $sql = new Sql($this->db);
        
        $updateData = [
            'str_nombre_usuario' => $data['str_nombre_usuario'] ?? null,
            'id_perfil' => $data['id_perfil'] ?? null,
            'id_estado_usuario' => $data['id_estado_usuario'] ?? null,
            'str_correo' => $data['str_correo'] ?? null,
            'str_numero_celular' => $data['str_numero_celular'] ?? null,
            'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
        ];

        // Solo actualizar contraseña si se proporciona
        if (!empty($data['str_pwd'])) {
            $updateData['str_pwd'] = password_hash($data['str_pwd'], PASSWORD_BCRYPT);
        }

        // Solo actualizar imagen si se proporciona
        if (!empty($data['imagen'])) {
            $updateData['imagen'] = $data['imagen'];
        }

        $update = $sql->update('usuario')
            ->set($updateData)
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($update);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Eliminar usuario
     */
    public function deleteUsuario(int $id): bool
    {
        $sql = new Sql($this->db);
        
        // No permitir eliminar al admin
        $usuario = $this->getUsuario($id);
        if ($usuario && $usuario['str_nombre_usuario'] === 'admin') {
            return false;
        }

        $delete = $sql->delete('usuario')
            ->where(['id' => $id]);

        $stmt = $sql->prepareStatementForSqlObject($delete);
        $result = $stmt->execute();

        return $result->getAffectedRows() > 0;
    }

    /**
     * Obtener todos los perfiles para dropdown
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

    /**
     * Obtener todos los estados
     */
    public function getEstadosForSelect(): array
    {
        $sql = new Sql($this->db);
        
        $select = $sql->select('estado_usuario')
            ->order('str_nombre ASC');

        $stmt = $sql->prepareStatementForSqlObject($select);
        $result = $stmt->execute();

        $estados = [];
        foreach ($result as $row) {
            $estados[$row['id']] = $row['str_nombre'];
        }

        return $estados;
    }
}

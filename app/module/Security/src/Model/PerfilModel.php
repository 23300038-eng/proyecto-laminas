<?php

declare(strict_types=1);

namespace Security\Model;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Sql;

class PerfilModel
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function getPerfiles(int $limit = 5, int $offset = 0): array
    {
        $sql = new Sql($this->db);

        $select = $sql->select(['p' => 'perfil'])
            ->columns(['id', 'str_nombre_perfil', 'descripcion', 'creado_en', 'actualizado_en'])
            ->join(
                ['pp' => 'permisos_perfil'],
                'pp.id_perfil = p.id',
                ['modulos_con_acceso' => new Expression(
                    "COUNT(CASE WHEN pp.bit_agregar OR pp.bit_editar OR pp.bit_consulta OR pp.bit_eliminar OR pp.bit_detalle THEN 1 END)"
                )],
                'left'
            )
            ->group(['p.id'])
            ->order('p.creado_en DESC')
            ->limit($limit)
            ->offset($offset);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        return array_values(iterator_to_array($result));
    }

    public function getPerfilesTotal(): int
    {
        $sql = new Sql($this->db);
        $select = $sql->select('perfil');
        $select->columns([new Expression('COUNT(*) as total')]);

        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        return (int) ($row['total'] ?? 0);
    }

    public function getPerfil(int $id): ?array
    {
        $sql = new Sql($this->db);
        $select = $sql->select('perfil')->where(['id' => $id]);
        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (array) $row : null;
    }

    public function createPerfil(array $data): int
    {
        $sql = new Sql($this->db);
        $statement = $this->db->createStatement(
            'INSERT INTO perfil (str_nombre_perfil, bit_administrador, descripcion, creado_en, actualizado_en)
             VALUES (?, false, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             RETURNING id',
            [
                trim((string) ($data['str_nombre_perfil'] ?? '')),
                trim((string) ($data['descripcion'] ?? '')) !== '' ? trim((string) $data['descripcion']) : null,
            ]
        );

        $result = $statement->execute();
        $row = $result->current();

        return (int) (($row['id'] ?? $row[0] ?? 0));
    }

    public function updatePerfil(int $id, array $data): bool
    {
        $sql = new Sql($this->db);

        $update = $sql->update('perfil')
            ->set([
                'str_nombre_perfil' => $data['str_nombre_perfil'] ?? null,
                'bit_administrador' => false,
                'descripcion' => $data['descripcion'] ?? null,
                'actualizado_en' => new Expression('CURRENT_TIMESTAMP'),
            ])
            ->where(['id' => $id]);

        $result = $sql->prepareStatementForSqlObject($update)->execute();

        return $result->getAffectedRows() > 0;
    }

    public function deletePerfil(int $id): bool
    {
        $sql = new Sql($this->db);

        $selectCount = $sql->select('usuario')
            ->columns([new Expression('COUNT(*) as total')])
            ->where(['id_perfil' => $id]);

        $row = $sql->prepareStatementForSqlObject($selectCount)->execute()->current();

        if ((int) ($row['total'] ?? 0) > 0) {
            return false;
        }

        $delete = $sql->delete('perfil')->where(['id' => $id]);
        $result = $sql->prepareStatementForSqlObject($delete)->execute();

        return $result->getAffectedRows() > 0;
    }
}

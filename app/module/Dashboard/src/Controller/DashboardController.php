<?php

declare(strict_types=1);

namespace Dashboard\Controller;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Security\Support\AccessHelper;

class DashboardController extends AbstractActionController
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function indexAction()
    {
        $dashboardModuleId = AccessHelper::getModuleIdByName($this->db, 'Panel de Control');
        $canViewDashboard = $dashboardModuleId !== null
            ? AccessHelper::hasModulePermissionById($dashboardModuleId, 'consulta')
            : AccessHelper::isAdmin();

        if (!$canViewDashboard) {
            return $this->redirect()->toRoute('home');
        }

        return new ViewModel([
            'titulo' => 'Panel de Control',
            'menu_modulos' => AccessHelper::buildMenu($this->db),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Panel de Control', 'url' => null],
            ],
            'estadisticas' => [
                'usuarios' => $this->count('usuario'),
                'modulos' => $this->count('modulo'),
                'submodulos' => $this->count('submodulo'),
                'perfiles' => $this->count('perfil'),
            ],
            'usuarios_recientes' => $this->getUsuariosRecientes(),
        ]);
    }

    public function principal1Item1Action()
    {
        return $this->renderModuloDemo(
            'Principal 1.1',
            'Vista operativa de ejemplo del módulo Principal 1. Se mantiene visible solo para perfiles con acceso al módulo.'
        );
    }

    public function principal1Item2Action()
    {
        return $this->renderModuloDemo(
            'Principal 1.2',
            'Sección complementaria de ejemplo para el módulo Principal 1, diseñada para demostrar navegación y permisos.'
        );
    }

    public function principal2Item1Action()
    {
        return $this->renderModuloDemo(
            'Principal 2.1',
            'Vista demostrativa del módulo Principal 2 para probar accesos, layout y navegación dinámica desde la base de datos.'
        );
    }

    public function principal2Item2Action()
    {
        return $this->renderModuloDemo(
            'Principal 2.2',
            'Pantalla de ejemplo del segundo módulo principal. Su acceso depende del perfil asignado al usuario actual.'
        );
    }

    private function renderModuloDemo(string $titulo, string $descripcion): ViewModel
    {
        return new ViewModel([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'menu_modulos' => AccessHelper::buildMenu($this->db),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => $titulo, 'url' => null],
            ],
        ]);
    }

    private function count(string $table, string $where = '1=1'): int
    {
        try {
            $row = $this->db->query(
                sprintf('SELECT COUNT(*) AS total FROM %s WHERE %s', $table, $where),
                []
            )->current();

            return (int) ($row['total'] ?? 0);
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    private function getUsuariosRecientes(): array
    {
        try {
            $result = $this->db->query(
                'SELECT 
                    u.id,
                    u.str_nombre_usuario,
                    u.str_correo,
                    p.str_nombre_perfil,
                    e.str_nombre AS str_nombre_estado
                 FROM usuario u
                 INNER JOIN perfil p ON p.id = u.id_perfil
                 LEFT JOIN estado_usuario e ON e.id = u.id_estado_usuario
                 ORDER BY u.creado_en DESC
                 LIMIT 5',
                []
            );

            return array_values(iterator_to_array($result));
        } catch (\Throwable $exception) {
            return [];
        }
    }
}

<?php

declare(strict_types=1);

namespace Dashboard\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\AdapterInterface;

class DashboardController extends AbstractActionController
{
    private AdapterInterface $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    private function getModulosParaSidebar(): array
    {
        try {
            $resultado = $this->db->query('SELECT * FROM modulo WHERE bit_activo = true ORDER BY int_orden');
            $modulosDb = $resultado->execute();
            
            $modulosAgrupados = [
                'Administración' => [],
                'Otros' => []
            ];
            
            foreach ($modulosDb as $modulo) {
                $item = [
                    'nombre' => $modulo['str_nombre_modulo'],
                    'icono' => $modulo['str_icono'] ?? '',
                    'url' => '#'
                ];
                
                $nombre = strtolower($modulo['str_nombre_modulo']);
                if (strpos($nombre, 'perfil') !== false) {
                    $item['url'] = $this->url()->fromRoute('security', ['action' => 'perfil']);
                    $modulosAgrupados['Administración'][] = $item;
                } elseif (strpos($nombre, 'usuario') !== false) {
                    $item['url'] = $this->url()->fromRoute('security', ['action' => 'usuario']);
                    $modulosAgrupados['Administración'][] = $item;
                } elseif (strpos($nombre, 'modulo') !== false) {
                    $item['url'] = $this->url()->fromRoute('security', ['action' => 'modulo']);
                    $modulosAgrupados['Administración'][] = $item;
                } elseif (strpos($nombre, 'permiso') !== false) {
                    $item['url'] = $this->url()->fromRoute('security', ['action' => 'permisos-perfil']);
                    $modulosAgrupados['Administración'][] = $item;
                } else {
                    $modulosAgrupados['Otros'][] = $item;
                }
            }
            
            return array_filter($modulosAgrupados, fn($items) => !empty($items));
        } catch (\Exception $e) {
            return [];
        }
    }

    public function indexAction()
    {
        return new ViewModel([
            'modulos' => $this->getModulosParaSidebar(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Dashboard', 'url' => null],
            ],
        ]);
    }

    // Principal 1
    public function principal1Item1Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 1.1',
            'descripcion' => 'Este es un módulo de demostración',
            'modulos' => $this->getModulosParaSidebar(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 1', 'url' => null],
                ['nombre' => 'Principal 1.1', 'url' => null],
            ],
        ]);
    }

    public function principal1Item2Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 1.2',
            'descripcion' => 'Este es un módulo de demostración',
            'modulos' => $this->getModulosParaSidebar(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 1', 'url' => null],
                ['nombre' => 'Principal 1.2', 'url' => null],
            ],
        ]);
    }

    // Principal 2
    public function principal2Item1Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 2.1',
            'descripcion' => 'Este es un módulo de demostración',
            'modulos' => $this->getModulosParaSidebar(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 2', 'url' => null],
                ['nombre' => 'Principal 2.1', 'url' => null],
            ],
        ]);
    }

    public function principal2Item2Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 2.2',
            'descripcion' => 'Este es un módulo de demostración',
            'modulos' => $this->getModulosParaSidebar(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 2', 'url' => null],
                ['nombre' => 'Principal 2.2', 'url' => null],
            ],
        ]);
    }
}

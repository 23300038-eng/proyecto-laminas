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

    /**
     * Construye el menú dinámico filtrando por permisos del usuario.
     * - Administrador: ve todos los módulos activos.
     * - No-admin: solo ve módulos donde su perfil tenga al menos un permiso.
     */
/**
     * Menú dinámico usando tabla modulo + submodulo, filtrado por permisos.
     */
    private function getModulosParaMenu(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $esAdmin   = !empty($_SESSION['bit_administrador']);
        $permisos  = $_SESSION['permisos'] ?? [];

        // IDs de módulos permitidos
        $permitidos = null;
        if (!$esAdmin) {
            $permitidos = [];
            foreach ($permisos as $idM => $bits) {
                if ($bits['agregar'] || $bits['editar'] || $bits['consulta'] || $bits['eliminar'] || $bits['detalle']) {
                    $permitidos[] = (int)$idM;
                }
            }
        }

        // Rutas estáticas para submodulos de Seguridad
        $rutasSeguridad = [
            'perfil'          => '/security/perfil',
            'módulo'          => '/security/modulo',
            'modulo'          => '/security/modulo',
            'permisos-perfil' => '/security/permiso-perfil',
            'permisos'        => '/security/permiso-perfil',
            'permiso'         => '/security/permiso-perfil',
            'usuario'         => '/security/usuario',
        ];

        try {
            $sqlMod = "SELECT id, str_nombre_modulo, str_icono FROM modulo WHERE bit_activo = true ORDER BY int_orden ASC";
            $modulos = iterator_to_array($this->db->query($sqlMod)->execute());

            $menu = [];
            foreach ($modulos as $m) {
                $idMod = (int)$m['id'];
                // Filtrar si no tiene permiso
                if ($permitidos !== null && !in_array($idMod, $permitidos, true)) {
                    continue;
                }

                // Obtener submodulos
                $sqlSub = "SELECT id, str_nombre_submodulo, str_ruta FROM submodulo WHERE id_modulo = ? AND bit_activo = true ORDER BY int_orden ASC";
                $subs   = iterator_to_array($this->db->query($sqlSub, [$idMod])->execute());

                $items = [];
                foreach ($subs as $sub) {
                    $ruta  = $sub['str_ruta'] ?? '#';
                    $nombre = $sub['str_nombre_submodulo'];
                    // Intentar ruta estática para seguridad
                    $key = strtolower($nombre);
                    foreach ($rutasSeguridad as $k => $r) {
                        if (strpos($key, $k) !== false) {
                            $ruta = $r;
                            break;
                        }
                    }
                    $items[] = ['nombre' => $nombre, 'url' => $ruta, 'icono' => ''];
                }

                if (!empty($items)) {
                    $menu[$m['str_nombre_modulo']] = $items;
                }
            }
            return $menu;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function indexAction()
    {
        return new ViewModel([
            'modulos'     => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Dashboard', 'url' => null],
            ],
        ]);
    }

    public function principal1Item1Action()
    {
        return new ViewModel([
            'titulo'      => 'Principal 1.1',
            'descripcion' => 'Módulo de demostración',
            'modulos'     => $this->getModulosParaMenu(),
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
            'titulo'      => 'Principal 1.2',
            'descripcion' => 'Módulo de demostración',
            'modulos'     => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 1', 'url' => null],
                ['nombre' => 'Principal 1.2', 'url' => null],
            ],
        ]);
    }

    public function principal2Item1Action()
    {
        return new ViewModel([
            'titulo'      => 'Principal 2.1',
            'descripcion' => 'Módulo de demostración',
            'modulos'     => $this->getModulosParaMenu(),
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
            'titulo'      => 'Principal 2.2',
            'descripcion' => 'Módulo de demostración',
            'modulos'     => $this->getModulosParaMenu(),
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 2', 'url' => null],
                ['nombre' => 'Principal 2.2', 'url' => null],
            ],
        ]);
    }
}

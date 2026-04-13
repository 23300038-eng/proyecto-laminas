<?php

declare(strict_types=1);

namespace Application\Helper;

use Laminas\Mvc\Controller\AbstractActionController;

class ModuleHelper
{
    /**
     * Obtener módulos organizados por sección para la barra lateral
     */
    public static function getModulosParaSidebar($moduloModel, $url): array
    {
        try {
            $modulos = $moduloModel->getModulos();
            $modulosAgrupados = [
                'Administración' => [],
                'Otros' => []
            ];
            
            foreach ($modulos as $modulo) {
                $item = [
                    'nombre' => $modulo['str_nombre_modulo'],
                    'icono' => $modulo['str_icono'] ?? '',
                    'url' => '#'
                ];
                
                // Mapear módulos a URLs según su nombre
                $nombre = strtolower($modulo['str_nombre_modulo']);
                if (strpos($nombre, 'perfil') !== false) {
                    $item['url'] = $url('security', ['action' => 'perfil']);
                    $modulosAgrupados['Administración'][] = $item;
                } elseif (strpos($nombre, 'usuario') !== false) {
                    $item['url'] = $url('security', ['action' => 'usuario']);
                    $modulosAgrupados['Administración'][] = $item;
                } elseif (strpos($nombre, 'modulo') !== false) {
                    $item['url'] = $url('security', ['action' => 'modulo']);
                    $modulosAgrupados['Administración'][] = $item;
                } elseif (strpos($nombre, 'permiso') !== false) {
                    $item['url'] = $url('security', ['action' => 'permisos-perfil']);
                    $modulosAgrupados['Administración'][] = $item;
                } else {
                    $modulosAgrupados['Otros'][] = $item;
                }
            }
            
            // Eliminar secciones vacías
            return array_filter($modulosAgrupados, fn($items) => !empty($items));
        } catch (\Exception $e) {
            return [];
        }
    }
}

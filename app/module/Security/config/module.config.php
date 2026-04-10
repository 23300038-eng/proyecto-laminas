<?php

declare(strict_types=1);

namespace Security;

use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'security' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/security[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\SecurityController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\SecurityController::class => Factory\SecurityControllerFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'template_map' => [
            'security/perfil' => __DIR__ . '/../view/security/perfil.phtml',
            'security/perfil-add' => __DIR__ . '/../view/security/perfil-add.phtml',
            'security/perfil-edit' => __DIR__ . '/../view/security/perfil-edit.phtml',
            'security/perfil-detalle' => __DIR__ . '/../view/security/perfil-detalle.phtml',
            'security/modulo' => __DIR__ . '/../view/security/modulo.phtml',
            'security/modulo-add' => __DIR__ . '/../view/security/modulo-add.phtml',
            'security/modulo-edit' => __DIR__ . '/../view/security/modulo-edit.phtml',
            'security/modulo-detalle' => __DIR__ . '/../view/security/modulo-detalle.phtml',
            'security/usuario' => __DIR__ . '/../view/security/usuario.phtml',
            'security/usuario-add' => __DIR__ . '/../view/security/usuario-add.phtml',
            'security/usuario-edit' => __DIR__ . '/../view/security/usuario-edit.phtml',
            'security/usuario-detalle' => __DIR__ . '/../view/security/usuario-detalle.phtml',
            'security/permiso-perfil' => __DIR__ . '/../view/security/permiso-perfil.phtml',
            'security/permiso-perfil-add' => __DIR__ . '/../view/security/permiso-perfil-add.phtml',
            'security/permiso-perfil-edit' => __DIR__ . '/../view/security/permiso-perfil-edit.phtml',
            'security/permiso-perfil-detalle' => __DIR__ . '/../view/security/permiso-perfil-detalle.phtml',
        ],
    ],
];


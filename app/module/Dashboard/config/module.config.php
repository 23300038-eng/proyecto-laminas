<?php

declare(strict_types=1);

namespace Dashboard;

use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'dashboard' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/dashboard[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\DashboardController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'principal1' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/principal1[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\DashboardController::class,
                        'action'     => 'principal1-item1',
                    ],
                ],
            ],
            'principal2' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/principal2[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\DashboardController::class,
                        'action'     => 'principal2-item1',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\DashboardController::class => Factory\DashboardControllerFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'template_map' => [
            'dashboard/index' => __DIR__ . '/../view/dashboard/index.phtml',
            'dashboard/principal1-item1' => __DIR__ . '/../view/dashboard/principal1-item1.phtml',
            'dashboard/principal1-item2' => __DIR__ . '/../view/dashboard/principal1-item2.phtml',
            'dashboard/principal2-item1' => __DIR__ . '/../view/dashboard/principal2-item1.phtml',
            'dashboard/principal2-item2' => __DIR__ . '/../view/dashboard/principal2-item2.phtml',
        ],
    ],
];

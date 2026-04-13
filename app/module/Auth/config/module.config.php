<?php

declare(strict_types=1);

namespace Auth;

use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'auth' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/auth[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9-]*',
                    ],
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\AuthController::class => Factory\AuthControllerFactory::class,
        ],
    ],

    'middleware_pipeline' => [
        'authentication' => [
            'middleware' => Middleware\AuthenticationMiddleware::class,
            'priority' => 1000, // Ejecutar primero
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'template_map' => [
            'auth/login' => __DIR__ . '/../view/auth/login.phtml',
            'auth/logout' => __DIR__ . '/../view/auth/logout.phtml',
        ],
    ],
];

<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'auth-login-fallback' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/auth/login',
                    'defaults' => [
                        'controller' => \Auth\Controller\AuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],
            'auth-logout-fallback' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/auth/logout',
                    'defaults' => [
                        'controller' => \Auth\Controller\AuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            'auth-root-fallback' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/auth',
                    'defaults' => [
                        'controller' => \Auth\Controller\AuthController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'mi-perfil' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/mi-perfil',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'mi-perfil',
                    ],
                ],
            ],
            'modular' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/modulos/:modulo[/:submodulo]',
                    'constraints' => [
                        'modulo' => '[a-zA-Z0-9\-]+',
                        'submodulo' => '[a-zA-Z0-9\-]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'modular',
                    ],
                ],
            ],
            'modular-short' => [
                'type' => Regex::class,
                'options' => [
                    'regex' => '^/(?<modulo>(?!auth(?:/|$)|security(?:/|$)|dashboard(?:/|$)|application(?:/|$)|mi-perfil(?:/|$)|modulos(?:/|$)|css(?:/|$)|js(?:/|$)|img(?:/|$)|images(?:/|$)|uploads(?:/|$)|favicon\.ico$)[a-zA-Z0-9\-]+)(?:/(?<submodulo>[a-zA-Z0-9\-]+))?$',
                    'spec' => '/%modulo%/%submodulo%',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'modular',
                    ],
                ],
            ],
            'application' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/application',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'action' => 'index',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => Factory\IndexControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy',
        ],
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'application/index/mi-perfil' => __DIR__ . '/../view/application/index/mi-perfil.phtml',
            'application/index/modular-module' => __DIR__ . '/../view/application/index/modular-module.phtml',
            'application/index/modular-submodule' => __DIR__ . '/../view/application/index/modular-submodule.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];

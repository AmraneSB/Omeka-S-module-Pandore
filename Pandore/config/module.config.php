<?php
namespace Pandore;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Router\Http\Literal;

return [
    'controllers' => [
        'factories' => [
            \Pandore\Controller\AdminController::class => InvokableFactory::class,
        ],
    ],

    'router' => [
        'routes' => [
            'admin-pandore' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin/pandore',
                    'defaults' => [
                        'controller' => \Pandore\Controller\AdminController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'pandore' => __DIR__ . '/../view',
        ],
    ],
];

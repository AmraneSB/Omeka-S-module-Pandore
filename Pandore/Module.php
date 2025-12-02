<?php
namespace Pandore;

use Omeka\Module\AbstractModule;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\Factory\InvokableFactory;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return [
            'controllers' => [
                'factories' => [
                    Controller\AdminController::class => InvokableFactory::class,
                ],
            ],
            'router' => [
                'routes' => [
                    'admin/pandore' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/admin/pandore',
                            'defaults' => [
                                '__NAMESPACE__' => 'Pandore\Controller',
                                'controller' => Controller\AdminController::class,
                                'action' => 'index',
                            ],
                        ],
                    ],
                ],
            ],
            'view_manager' => [
                'template_path_stack' => [
                    __DIR__ . '/view',
                ],
            ],
        ];
    }
}

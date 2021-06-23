<?php

namespace Inlead;

use Inlead\Controller\AbstractBaseFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;

$config = [
    'router' => [
        'routes' => [
            'inlead-manager' => [
                'type' => Literal::class,
                'options' => [
                    'verb' => 'get',
                    'route' => '/api/v1/manager',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'getList'
                    ]
                ],
//                'may_terminate' => true,
//                'child_routes' => [
            ],
            'inlead-manager-details' => [
                'type' => 'segment',
                'options' => [
                    'verb' => 'get',
                    'route' => '/api/v1/manager/:id',
                    'constraints' => [
                        'id' => '\d+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'getList'
                    ]
                ],
            ],
            'inlead-manager-create' => [
                'type' => Method::class,
                'options' => [
                    'verb' => 'post',
                    'route' => '/api/v1/manager/create',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'create'
                    ],
                ],
            ],
            'inlead-manager-destroy' => [
                'type' => 'method',
                'options' => [
                    'verb' => 'delete',
                    'route' => '/api/v1/manager/:id',
                    'constraints' => [
                        'id' => '\d+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'destroy'
                    ],
                ],
            ]
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\ManagerAPIController::class => AbstractBaseFactory::class,
        ],
    ],
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'Inlead\Db\Table\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'Inlead\Db\Row\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'alias' => [
            'Inlead\DbRowPluginManager' => 'Inlead\Db\Row\PluginManager',
            'Inlead\DbTablePluginManager' => 'Inlead\Db\Table\PluginManager',
        ],
    ],
    'inlead' => [
        'plugin_managers' => [
            'db_row' => [ /* see Inlead\Db\Row\PluginManager for defaults */],
            'db_table' => [ /* see Inlead\Db\Table\PluginManager for defaults */],
        ]
    ],
];

return $config;

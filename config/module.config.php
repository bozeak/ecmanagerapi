<?php

namespace Inlead;

use Inlead\Controller\AbstractBaseFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Segment;

$config = [
    'router' => [
        'routes' => [
            'inlead.manager' => [
                'type' => Literal::class,
                'options' => [
                    'verb' => 'get',
                    'route' => '/api/manager',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'getList'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'details' => [
                        'type' => Segment::class,
                        'options' => [
                            'verb' => 'get',
                            'route' => '/:id',
                            'constraints' => [
                                'id' => '\d+',
                            ],
                        ],
                    ]
                ],
            ],
            'inlead.manager.create' => [
                'type' => Method::class,
                'options' => [
                    'verb' => 'post',
                    'route' => '/api/manager/create',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'create'
                    ],
                ],
            ],
            'inlead.manager.update' => [
                'type' => Method::class,
                'options' => [
                    'verb' => 'put',
                    'route' => '/api/manager/update',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'update'
                    ],
                ],
            ],
            'inlead.manager.destroy' => [
                'type' => 'method',
                'options' => [
                    'verb' => 'delete',
                    'route' => '/api/manager',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'destroy'
                    ],
                ],
            ],
            'inlead.manager.import.harvest' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/api/manager/start-import',
                    'defaults' => [
                        'controller' => Controller\ManagerAPIController::class,
                        'action' => 'startImport',
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

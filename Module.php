<?php
/**
 * Template for code module for storing local overrides.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Module
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
namespace Inlead;

//use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\MvcEvent;

/**
 * Template for code module for storing local overrides.
 *
 * @category VuFind
 * @package  Module
 * @ahor   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
class Module implements ConfigProviderInterface
{
    /**
     * Get module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * Bootstrap the module
     *
     * @param MvcEvent $e Event
     *
     * @return void
     */
    public function onBootstrap(MvcEvent $e)
    {
    }

    // Add this method:
//    public function getServiceConfig()
//    {
//        return [
//            'factories' => [
//                "Inlead\Model\ConsumerTable" => function($container) {
//                    $tableGateway = $container->get("Inlead\Model\ConsumerTableGateway");
//                    return new Model\ConsumerTable($tableGateway);
//                },
//                "Inlead\Model\ConsumerTableGateway" => function ($container) {
//                    $dbAdapter = $container->get(\Laminas\Db\Adapter\AdapterInterface::class);
////                    $dbAdapter = $container->get();
//                    $resultSetPrototype = new ResultSet();
//                    $resultSetPrototype->setArrayObjectPrototype(new Model\Consumer());
//                    return new TableGateway('inlead_consumer', $dbAdapter, null, $resultSetPrototype);
//                },
//            ],
//        ];
//    }
//
    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\ManagerAPIController::class => function($container) {
                    return new Controller\ManagerAPIController(
                        $container->get(Model\Consumer::class)
                    );
                },
                Controller\CreateConsumerController::class => function($container) {
                    return new Controller\CreateConsumerController(
                        $container->get(Model\Consumer::class)
                    );
                },
            ],
        ];
    }
}

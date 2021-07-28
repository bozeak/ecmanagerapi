<?php


namespace Inlead\Controller;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use VuFindApi\Formatter\FacetFormatter;

class SearchAPIControllerFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{

    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        return new $requestedName(
            $container,
            $container->get(\Inlead\Formatter\MarcRecordFormatter::class),
            $container->get(\VuFindApi\Formatter\FacetFormatter::class),
        );
    }
}

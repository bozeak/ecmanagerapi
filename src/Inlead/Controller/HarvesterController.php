<?php


namespace Inlead\Controller;


use Inlead\Db\Table\PluginManager;
use Laminas\Http\Client;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use VuFindApi\Controller\ApiTrait;

class HarvesterController extends AbstractActionController
{
    use ApiTrait;

    /**
     * Service manager
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service locator
     */
    public function __construct(ServiceLocatorInterface $sm)
    {
        $this->serviceLocator = $sm;
    }

    public function harvestAction()
    {

        $application = new Application();
        $consumerId = $this->params('id');

        $sm = $this->serviceLocator->get(PluginManager::class)->get('consumer');
        $consumer = $sm->getConsumer($consumerId);
        $ur = $consumer->current()->toArray();

        $client = new Client($ur['source_url']);


        $input = new ArrayInput([
            'command' => 'harvest/harvest_oai',
            // (optional) define the value of command arguments
            'target' => $ur['name'],
//            'ini' => 'oai.ini',
        ]);

//        $exec = new InleadHarvesterCommand($client, 'sambib');
        $output = new BufferedOutput();

        $application->run($input, $output);
        $content = $output->fetch();

        $response = new Response();

        return $response->setContent($content);

//        return $this->output(['message' => 'Harvesting was initalized.', 'content' => $content], 'OK', 200);
    }
}

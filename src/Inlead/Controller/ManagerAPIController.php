<?php


namespace Inlead\Controller;

use Exception;
use Inlead\Db\Table\PluginManager;
use JsonException;
use Laminas\Http\Client;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFindApi\Controller\ApiInterface;
use VuFindApi\Controller\ApiTrait;
use VuFindHarvest\OaiPmh\Harvester;
use VuFindHarvest\OaiPmh\HarvesterFactory;

class ManagerAPIController extends AbstractActionController implements ApiInterface
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

    /**
     * @return Response
     * @throws Exception
     */
    public function getListAction()
    {
        $service = $this->serviceLocator->get(PluginManager::class)
            ->get('Consumer');

        $id = (int)$this->params()->fromRoute('id');

        $data = $service->getAllConsumers();
        if (!empty($id)) {
            $data = $service->getConsumer($id);
        }

        $response = [
            'consumers' => $data->toArray(),
            'count' => count($data),
        ];

        return $this->output(
            $response,
            self::STATUS_OK,
            200
        );
    }

    /**
     * @return Response
     * @throws JsonException
     */
    public function createAction()
    {
        if ($this->request->isPost()) {
            $content = $this->request->getContent();

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            $service = $this->serviceLocator->get(PluginManager::class)
                ->get('Consumer');

            try {
                $consumer = $service->createConsumer($data);
            } catch (Exception $e) {
                print_r($e->getMessage());
            }

            return $this->output(
                [
                    'message' => 'Created.',
                    'consumer' => $consumer->toArray()
                ],
                self::STATUS_OK,
                201
            );
        }
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function destroyAction()
    {
        if ($this->request->isDelete()) {
            $req_body = $this->getRequest()->getContent();

            $body = (array)json_decode($req_body);
            $service = $this->serviceLocator->get(PluginManager::class)
                ->get('Consumer');

            $service->deleteConsumer($body['id']);

            return $this->output(
                [
                    'message' => 'Consumer deleted.'
                ],
                self::STATUS_OK
            );
        }
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function updateAction()
    {
        if ($this->request->isPut()) {
            $req_body = $this->getRequest()->getContent();
            $body = (array)json_decode($req_body);
            $service = $this->serviceLocator->get(PluginManager::class)
                ->get('Consumer');

            try {
                $consumer = $service->updateConsumer($body['id'], $body);
            } catch (Exception $e) {
                echo $e->getMessage();
            }

            return $this->output(
                [
                    'message' => 'Consumer updated.',
                    'consumer' => $consumer->toArray()
                ],
                self::STATUS_OK
            );
        }
    }

    /**
     * Start harvesting.
     * @throws Exception
     */
    public function startImportAction()
    {
        $service = $this->serviceLocator->get(PluginManager::class)
            ->get('Consumer');
        $consumers = $service->getAllConsumers()->toArray();

        $harvester = new HarvesterFactory();
        $client = new Client();
        $settings = [
            'url' => $consumers[0]['source_url'],
        ];
        $harvester->getHarvester($consumers[0]['name'], './local/harvest', $client, $settings);
        $a = 1;
        return $this->output(['asdfa' => 'wqerqw'], self::STATUS_OK);
    }

    public function getSwaggerSpecFragment()
    {
        // TODO: Implement getSwaggerSpecFragment() method.
    }
}

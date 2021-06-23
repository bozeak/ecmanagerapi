<?php


namespace Inlead\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use VuFindApi\Controller\ApiInterface;
use VuFindApi\Controller\ApiTrait;

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

    public function getListAction($aaaa = null)
    {
        $asdf = $this->serviceLocator->get(\Inlead\Db\Table\PluginManager::class)
            ->get('consumer');

        $consumers = [];

//        dd($this->serviceLocator->get('router'));
        $id = $this->params()->fromRoute('id');
//        dd($aaaa, $id);

        if (empty($id)) {
            $data = $asdf->select();
        }
        else {
            $data = $asdf->select(['id' => $id]);
        }

        foreach ($data as $item) {
            $consumers[] = $item->toArray();
        }

        $response = [
            'consumers' => $consumers
        ];
        return $this->output($response, self::STATUS_OK);
    }

    public function createAction()
    {
        $content = $this->request->getContent();

        $data = json_decode($content);

        $data = [
            'name' => $data->name,
            'source_url' => $data->source_url
        ];

        $asdf = $this->serviceLocator->get(\Inlead\Db\Table\PluginManager::class)
            ->get('consumer');

        try {
//            dd($data);
            $asdf->insert($data);
        }
        catch (\Exception $e) {
            print_r($e->getMessage());
        }

        return $this->output(['message' => 'Created.'], self::STATUS_OK);
    }

    public function destroyAction()
    {
        $id = $this->params()->fromRoute();

        if (!empty($id)) {
            $asdf = $this->serviceLocator->get(\Inlead\Db\Table\PluginManager::class)
                ->get('consumer');

            $asdf->delete(['id' => $id]);

            return $this->output(['message' => 'Consumer deleted.'], self::STATUS_OK);
        }
    }

//    public function showAction()
//    {
//        $id = $this->params('id');
//        $asdf = $this->serviceLocator->get(\Inlead\Db\Table\PluginManager::class)
//            ->get('Consumer');
//
//        $req = $asdf->select(['id' => $id])->current()->toArray();
//
//        return $this->output(['data' => $req] , self::STATUS_OK);
//    }

//    public function updateAction()
//    {
//        $id = $this->params('id');
//        $asdf = $this->serviceLocator->get(\Inlead\Db\Table\PluginManager::class)
//            ->get('Consumer');
//
//        $req = $asdf->select(['id' => $id])->current()->toArray();
//
//        return $this->output(['data' => 'Consumer updated'], self::STATUS_OK);
//    }

    public function getSwaggerSpecFragment()
    {
        // TODO: Implement getSwaggerSpecFragment() method.
    }
}

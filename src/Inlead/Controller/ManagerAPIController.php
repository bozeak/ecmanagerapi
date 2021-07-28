<?php

/**
 * @OA\Info(
 *     title="Manager API",
 *     version="1.0.0"
 * )
 * @OA\Server(
 *     url="http://10.0.0.98/vufind"
 * )
 *
 * @OA\Parameter(
 *     name="id",
 *     in="path",
 *     @OA\Schema(
 *         type="string"
 *     ),
 *     required=true
 * )
 */

namespace Inlead\Controller;

use Exception;
use Inlead\Db\Table\Consumer;
use Inlead\Db\Table\PluginManager;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use OpenApi\Annotations as OA;
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

    /**
     * @OA\Get(
     *     path="/api/manager",
     *     tags={"Consumer management"},
     *     summary="Returns consumer or a list of consumers.",
     *     description="Querying the consumers",
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Counld not find resource."
     *     )
     * )
     */

    /**
     * @OA\Get(
     *     path="/api/manager/{id}",
     *     tags={"Consumer management"},
     *     summary="Returns consumer details.",
     *     description="Querying the specific consumer",
     *     @OA\Parameter(
     *          ref="#/components/parameters/id"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *              mediaType="application/json"
     *          )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Counld not find resource."
     *     )
     * )
     */
    public function getListAction()
    {
        if ($this->request->isGet()) {
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

        return $this->output(['message' => 'Wrong method used.'], self::STATUS_ERROR);
    }

    /**
     * @OA\Post(
     *     path="/api/manager/create",
     *     tags={"Consumer management"},
     *     summary="Returns most accurate search result object",
     *     description="Search for an object, if found return it!",
     *     @OA\RequestBody(
     *         description="Consumer object",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Consumer"),
     *             @OA\Examples(
     *                 example="Consumer",
     *                 summary="Consumer insert",
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Could Not Find Resource"
     *     )
     * )
     */
    public function createAction()
    {
        if ($this->request->isPost()) {
            $content = $this->request->getContent();

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            /** @var Consumer $service */
            $service = $this->serviceLocator->get(PluginManager::class)
                ->get('Consumer');

            try {
                $consumer = $service->createConsumer($data);

                return $this->output(
                    [
                        'message' => 'Created.',
                        'consumer' => $consumer->toArray()
                    ],
                    self::STATUS_OK,
                    201
                );
            } catch (Exception $e) {
                print_r($e->getMessage());
            }
        } else {
            return $this->output(['message' => 'Wrong method used.'], self::STATUS_ERROR);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/manager/destroy",
     *     tags={"Consumer management"},
     *     summary="Deletes the requested consumer by given id.",
     *     @OA\RequestBody(
     *          description="ID must be passed",
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                 @OA\Property(
     *                     property="id",
     *                     type="int"
     *                 ),
     *                 example={"id": "999"}
     *             )
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Could Not Find Resource"
     *     )
     * )
     *
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
        } else {
            return $this->output(['message' => 'Wrong method used.'], self::STATUS_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/manager/update",
     *     tags={"Consumer management"},
     *     summary="Update the requested consumer by given id.",
     *     @OA\RequestBody(
     *          description="ID must be passed",
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(ref="#/components/schemas/Consumer"),
     *          ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Could Not Find Resource"
     *     )
     * )
     *
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

        return $this->output(['message' => 'Wrong method used.'], self::STATUS_ERROR);
    }

    public function getSwaggerSpecFragment()
    {
        // TODO: Implement getSwaggerSpecFragment() method.
    }
}

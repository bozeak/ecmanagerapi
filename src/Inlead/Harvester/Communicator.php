<?php


namespace Inlead\Harvester;


use Exception;
use Laminas\Http\Client;
use VuFindHarvest\ResponseProcessor\ResponseProcessorInterface;

class Communicator extends \VuFindHarvest\OaiPmh\Communicator
{

    public $settings;
    protected $retries;

    public function __construct($uri, Client $client, ResponseProcessorInterface $processor = null, $settings = null)
    {
        $this->settings = $settings;
        parent::__construct($uri, $client, $processor);
    }

    public function request($verb, $params = [])
    {
        if (!$this->retries) {
            $this->getOpensearchInfo($verb, $params);
        }

        $xml = $this->getOpensearchResponse($verb, $params);

        return $this->responseProcessor
            ? $this->responseProcessor->process($xml) : $xml;
    }

    /**
     * @param $verb
     * @param $params
     */
    public function getOpensearchInfo($verb, $params)
    {
        $request = $this->sendRequest($verb, $params);

        $content = $request->getBody();

        $xml = $this->responseProcessor->process($content);
        $hitCount = (string)$xml->result->hitCount;
        $this->retries = (int)ceil($hitCount / 50);
    }

    protected function sendRequest($verb, $params)
    {
//        var_dump($params);
        // Set up the request:
        $this->client->resetParameters(false, false); // keep cookies/auth
        $this->client->setUri($this->baseUrl);

        // Load request parameters:
        $query = $this->client->getRequest()->getQuery();
        foreach ($params as $key => $value) {
            $query->set($key, $value);
        }

        $neededParams = ['action', 'query', 'agency', 'profile', 'start', 'stepValue', 'outputType'];

        foreach ($neededParams as $neededParam) {
            $query->set($neededParam, $this->settings[$neededParam]);
        }

        $query->set('start', $params['start'] ?? 1);

        // Perform request:
        return $this->client->setMethod('GET')->send();
    }

    protected function getOpensearchResponse($verb, $params)
    {
        // Debug:
        $this->write(
            "Sending request: verb = {$verb}, params = " . print_r($params, true)
        );

        // Set up retry loop:
        do {
            $result = $this->sendRequest($verb, $params);
            if ($result->getStatusCode() == 503) {
                $delayHeader = $result->getHeaders()->get('Retry-After');
                $delay = is_object($delayHeader)
                    ? $delayHeader->getDeltaSeconds() : 0;
                if ($delay > 0) {
                    $this->writeLine(
                        "Received 503 response; waiting {$delay} seconds..."
                    );
                    sleep($delay);
                }
            } elseif (!$result->isSuccess()) {
                throw new Exception('HTTP Error ' . $result->getStatusCode());
            }
        } while ($result->getStatusCode() == 503);

        // If we got this far, there was no error -- send back response.
        return $result->getBody();
    }
}

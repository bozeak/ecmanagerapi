<?php


namespace Inlead\Harvester;


use Exception;
use Laminas\Http\Client;
use Symfony\Component\Console\Output\OutputInterface;
use VuFindHarvest\OaiPmh\RecordXmlFormatter;
use VuFindHarvest\RecordWriterStrategy\RecordWriterStrategyInterface;
use VuFindHarvest\ResponseProcessor\ResponseProcessorInterface;

class HarvesterFactory extends \VuFindHarvest\OaiPmh\HarvesterFactory
{
    public function getHarvester($target, $harvestRoot, Client $client = null,
                                 array $settings = [], OutputInterface $output = null
    )
    {
        $basePath = $this->getBasePath($harvestRoot, $target);
        $responseProcessor = $this->getResponseProcessor($basePath, $settings);
        $communicator = $this->getCommunicator(
            $this->configureClient($client, $settings),
            $settings, $responseProcessor, $target, $output
        );
        $formatter = $this->getFormatter($communicator, $settings, $output);
        $strategy = $this->getWriterStrategyFactory()
            ->getStrategy($basePath, $settings);
        $writer = $this->getWriter($strategy, $formatter, $settings);
        $stateManager = $this->getStateManager($basePath);
        $harvester = new Harvester($communicator, $writer, $stateManager, $settings);
        if ($writer = $this->getConsoleWriter($output, $settings)) {
            $harvester->setOutputWriter($writer);
        }
        return $harvester;
    }

//    protected function getFormatter(\VuFindHarvest\OaiPmh\Communicator $communicator, array $settings,
//                                    OutputInterface $output = null
//    ) {
//        // Build the formatter:
//        $formatter = new RecordXmlFormatter($settings);
//
//        // Load set names if we're going to need them:
//        if ($formatter->needsSetNames()) {
//            $loader = $this->getSetLoader($communicator, $settings);
//            if ($writer = $this->getConsoleWriter($output, $settings)) {
//                $loader->setOutputWriter($writer);
//            }
//            $formatter->setSetNames($loader->getNames());
//        }
//
//        return $formatter;
//    }


    protected function getWriter(RecordWriterStrategyInterface $strategy, \VuFindHarvest\OaiPmh\RecordXmlFormatter $formatter, array $settings)
    {
        $formatter_i = new \Inlead\Harvester\RecordXmlFormatter();
        return new RecordWriter($strategy, $formatter_i, $settings);
    }

    protected function getCommunicator(Client $client, array $settings,
                                       ResponseProcessorInterface $processor, $target,
                                       OutputInterface $output = null
    )
    {
        if (empty($settings['url'])) {
            throw new Exception("Missing base URL for {$target}.");
        }
        $comm = new Communicator($settings['url'], $client, $processor, $settings);
        // We only want the communicator to output messages if we are in verbose
        // mode; communicator messages are considered verbose output.
        if (($settings['verbose'] ?? false)
            && $writer = $this->getConsoleWriter($output, $settings)
        ) {
            $comm->setOutputWriter($writer);
        }
        return $comm;
    }


}

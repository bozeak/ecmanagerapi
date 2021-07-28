<?php

namespace Inlead\Harvester;

use VuFindHarvest\OaiPmh\StateManager;

class Harvester extends \VuFindHarvest\OaiPmh\Harvester
{
    /**
     * @var RecordWriter
     */
    protected $writer;

    protected $communicator;

    protected $settings;

    public function __construct(Communicator $communicator, RecordWriter $writer, StateManager $stateManager, $settings = [])
    {
        $this->settings = $settings;
        parent::__construct($communicator, $writer, $stateManager, $settings);
    }

    /**
     * Harvest all available documents.
     *
     * @return void
     */
    public function launch()
    {
        // Normalize sets setting to an array:
        $sets = (array)$this->set;
        if (empty($sets)) {
            $sets = [null];
        }

        // Load last state, if applicable (used to recover from server failure).
        if ($state = $this->stateManager->loadState()) {
            $this->write("Found saved state; attempting to resume.\n");
            list($resumeSet, $resumeToken, $this->startDate) = $state;
        }

        // Loop through all of the selected sets:
        foreach ($sets as $set) {
//            var_dump($set);
            // If we're resuming and there are multiple sets, find the right one.
            if (isset($resumeToken) && $resumeSet != $set) {
                continue;
            }

            // If we have a token to resume from, pick up there now...
            if (isset($resumeToken)) {
                $token = $resumeToken;
                unset($resumeToken);
            } else {
                // ...otherwise, start harvesting at the requested date:
                $token = $this->getRecordsByDate(
                    $this->startDate, $set, $this->harvestEndDate
                );
            }

            // Keep harvesting as long as a resumption token is provided:
            $start = 1;
            while ($token !== false) {
                $start = $start + 50;
                // Save current state in case we need to resume later:
                $this->stateManager->saveState($set, $token, $this->startDate);
                $token = $this->getRecordsByToken($token, $start);
            }
        }

        // If we made it this far, all was successful, so we should clean up
        // the stored state.
        $this->stateManager->clearState();
    }

    /**
     * Harvest records via OAI-PMH using resumption token.
     *
     * @param string $token Resumption token.
     *
     * @return mixed        Resumption token if provided, false if finished
     */
    protected function getRecordsByToken($token, $start = 1)
    {
        return $this->getRecords(['resumptionToken' => (string)$token, 'start' => $start]);
    }

    protected function getRecords($params)
    {
        // Make the OAI-PMH request:
        $response = $this->sendRequest('search', $params);

        // Save the records from the response:
        if ($response->result->searchResult) {
            $this->writeLine(
                'Processing ' . count($response->result->searchResult) . " records..."
            );
            $endDate = $this->writer->write($response->result->searchResult);
        }

        // If we have a resumption token, keep going; otherwise, we're done -- save
        // the end date.
        if (isset($response->result->more)
            && !empty($response->result->more)) {
            return $response->result->more;
        }

        if (isset($endDate) && $endDate > 0) {
            $dateFormat = ($this->granularity === 'YYYY-MM-DD') ?
                'Y-m-d' : 'Y-m-d\TH:i:s\Z';
            $this->stateManager->saveDate(date($dateFormat, $endDate));
        }
        return false;
    }

    protected function sendRequest($verb, $params = [])
    {
        $response = $this->communicator->request($verb, $params);
        $this->checkResponseForErrors($response);
        return $response;
    }
}

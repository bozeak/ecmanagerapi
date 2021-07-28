<?php


namespace Inlead\Harvester;


use VuFindHarvest\RecordWriterStrategy\RecordWriterStrategyInterface;

class RecordWriter extends \VuFindHarvest\OaiPmh\RecordWriter
{
    /**
     * XML record formatter
     *
     * @var RecordXmlFormatter
     */
    protected $recordFormatter;

    /**
     * Writer strategy
     *
     * @var RecordWriterStrategyInterface
     */
    protected $strategy;

    public function __construct($strategy, $formatter, $settings = [])
    {
        $this->recordFormatter = $formatter;
        $this->strategy = $strategy;
        parent::__construct($strategy, $formatter, $settings);
    }

    public function write($records)
    {
        // Array for tracking successfully harvested IDs:
        $harvestedIds = [];

        // Date of most recent record encountered:
        $endDate = 0;

        $this->strategy->beginWrite();

        // Loop through the records:
        foreach ($records as $record) {
            // Get the ID of the current record:
            $id = $this->extractID($record);

            // Save the current record, either as a deleted or as a regular file:
//            $attribs = $record->header->attributes();
//            if (strtolower($attribs['status']) == 'deleted') {
//                $this->strategy->addDeletedRecord($id);
//            } else {
                $recordXML = $this->recordFormatter->format($id, $record);
//                var_dump($recordXML);
                $this->strategy->addRecord($id, $recordXML);
                $harvestedIds[] = $id;
//            }

            // If the current record's date is newer than the previous end date,
            // remember it for future reference:
            $date = $this->normalizeDate($record->collection->object->creationDate);
            if ($date && $date > $endDate) {
                $endDate = $date;
            }
        }

        $this->strategy->endWrite();

        $this->writeHarvestedIdsLog($harvestedIds);

        return $endDate;
    }

    /**
     * Normalize a date to a Unix timestamp.
     *
     * @param string $date Date (ISO-8601 or YYYY-MM-DD HH:MM:SS)
     *
     * @return integer     Unix timestamp (or false if $date invalid)
     */
    protected function normalizeDate($date)
    {
        // Remove timezone markers -- we don't want PHP to outsmart us by adjusting
        // the time zone!
        $date = str_replace(['T', 'Z'], [' ', ''], $date);

        // Translate to a timestamp:
        return strtotime($date);
    }


    protected function extractID($record)
    {
        // Normalize to string:
        $id = (string) $record->collection->object->identifier;

        // Strip prefix if found:
        if (substr($id, 0, strlen($this->idPrefix)) == $this->idPrefix) {
            $id = substr($id, strlen($this->idPrefix));
        }

        // Apply regular expression matching:
        if (!empty($this->idSearch)) {
            $id = preg_replace($this->idSearch, $this->idReplace, $id);
        }

        // Return final value:
        return $id;
    }

}

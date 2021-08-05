<?php


namespace Inlead\Formatter;

use Laminas\View\HelperPluginManager;
use stdClass;
use VuFind\I18n\TranslatableString;
use VuFind\RecordDriver\AbstractBase;
use VuFindApi\Formatter\BaseFormatter;

class MarcRecordFormatter extends BaseFormatter
{

    /**
     * Record field definitions
     *
     * @var array
     */
    protected $recordFields;

    /**
     * View helper plugin manager
     *
     * @var HelperPluginManager
     */
    protected $helperManager;

    /**
     * Constructor
     *
     * @param array $recordFields Record field definitions
     * @param HelperPluginManager $helperManager View helper plugin manager
     */
    public function __construct($recordFields, HelperPluginManager $helperManager)
    {
        $this->recordFields = $recordFields;
        $this->helperManager = $helperManager;
    }

    /**
     * Get record field definitions.
     *
     * @return array
     */
    public function getRecordFields()
    {
        return $this->recordFields;
    }

    /**
     * @param $results
     * @param $requestedFields
     * @param bool $processMarc
     * @param array $marcFields
     * @return array
     */
    public function format($results, $requestedFields, bool $processMarc = false, array $marcFields = [])
    {
        $records = [];
        foreach ($results as $result) {
            $records[] = $this->getFields($result, $requestedFields);
        }

        $marcRecords = [];
        if (!empty($processMarc)) {
            foreach ($records as $key => $record) {
                if (!isset($record['fullRecord'])) {
                    continue;
                }
                $marcRecord = new stdClass();
                $marcRecord->id = $record['id'];
                $fullRecord = simplexml_load_string($record['fullRecord']);

                $fields = [];
                foreach ($fullRecord->record as $items) {
                    $marcRecord->leader = (string)$items->leader;

                    foreach ($items as $type => $item) {
                        if ('controlfield' === $type || 'datafield' === $type) {
                            $tag = (string)$item->attributes()['tag'];

                            if (in_array($tag, array_column($marcFields, 'tag'), true)) {
                                if ($type === 'controlfield' && $item->attributes() !== null) {
                                    $fields[][$tag] = (string)$item;
                                }

                                if ($type === 'datafield') {
                                    $tags = [];
                                    foreach ($item as $i) {
                                        $codes = (array)$i->attributes()['code'];
                                        foreach ($codes as $attribute) {
                                            if (!isset($marcFields[$tag]['codes'])
                                                || (isset($marcFields[$tag]['codes']) && in_array($attribute, $marcFields[$tag]['codes'], true))) {
                                                $tags['subfields'][$attribute] = (string)$i;
                                            }
                                        }
                                    }
                                    $fields[] = [
                                        $tag => $tags,
                                        "ind1" => (string)$item->attributes()['ind1'],
                                        "ind2" => (string)$item->attributes()['ind2'],
                                    ];
                                }
                            }
                        }
                    }
                }
                $marcRecord->fields = $fields;
                $marcRecords[] = $marcRecord;
            }
        }

        $this->filterArrayValues($records);

        return $processMarc ? $marcRecords : $records;
    }

    /**
     * Get fields from a record as an array
     *
     * @param AbstractBase $record Record driver
     * @param array $fields Fields to get
     *
     * @return array
     */
    protected function getFields($record, $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!isset($this->recordFields[$field])) {
                continue;
            }
            $method = $this->recordFields[$field]['vufind.method'];
            if (strncmp($method, 'Formatter::', 11) == 0) {
                $value = $this->{substr($method, 11)}($record);
            } else {
                $value = $record->tryMethod($method);
            }
            $result[$field] = $value;
        }
        // Convert any translation aware string classes to strings
        $translator = $this->helperManager->get('translate');
        array_walk_recursive(
            $result,
            function (&$value) use ($translator) {
                if (is_object($value)) {
                    if ($value instanceof TranslatableString) {
                        $value = [
                            'value' => (string)$value,
                            'translated' => $translator->translate($value)
                        ];
                    } else {
                        $value = (string)$value;
                    }
                }
            }
        );

        return $result;
    }

    public function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get raw data for a record as an array
     *
     * @param AbstractBase $record Record driver
     *
     * @return array
     */
    protected function getRawData($record)
    {
        $rawData = $record->tryMethod('getRawData');

        // Leave out spelling data
        unset($rawData['spelling']);

        return $rawData;
    }

    /**
     * Get full record for a record as XML
     *
     * @param AbstractBase $record Record driver
     *
     * @return string|null
     */
    protected function getFullRecord($record)
    {
        if ($xml = $record->tryMethod('getFilteredXML')) {
            return $xml;
        }
        $rawData = $record->tryMethod('getRawData');
        return $rawData['fullrecord'] ?? null;
    }

    /**
     * Get URLs
     *
     * @param AbstractBase $record Record driver
     *
     * @return array
     */
    protected function getURLs($record)
    {
        $recordHelper = $this->helperManager->get('record');
        return $recordHelper($record)->getLinkDetails();
    }

}

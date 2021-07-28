<?php


namespace Inlead\Harvester;


class RecordXmlFormatter extends \VuFindHarvest\OaiPmh\RecordXmlFormatter
{

    public function format($id, $recordObj)
    {
        $raw = trim($recordObj->collection->object->asXML());
//        print_r($raw);

        // Extract the actual metadata from inside the <metadata></metadata> tags;
        // there is probably a cleaner way to do this, but this simple method avoids
        // the complexity of dealing with namespaces in SimpleXML.
        //
        // We should also apply global search and replace at this time, if
        // applicable.
//        $record = $this->performGlobalReplace(
////            preg_replace('/(^<object[^\>]*>)|(<\/object>$)/m', '', $raw)
//            preg_replace('', '', $raw)
//        );

        $record = $raw;


//        print_r($record);
        // Collect attributes (for proper namespace resolution):
        $metadataAttributes = $this->extractMetadataAttributes($raw, $record);
//        var_dump($metadataAttributes);
//        var_dump("---------------------------------------------------------\n");


        // If we are supposed to inject any values, do so now inside the first
        // tag of the file:
        $insert = false;
//        $insert = $this->getIdAdditions($id)
//            . $this->getHeaderAdditions($recordObj->header);
        $xml = !empty($insert)
            ? preg_replace('/>/', '>' . $insert, $record, 1) : $record;

//        var_dump($xml);

//        // Build the final record:
        return trim(
            $this->fixNamespaces(
                $xml, $recordObj->getDocNamespaces(), $metadataAttributes
            )
        );
    }

    protected function extractMetadataAttributes($raw, $record)
    {
        // remove all attributes from extractedNs that appear deeper in xml; this
        // helps prevent fatal errors caused by the same namespace declaration
        // appearing twice in a single tag.
        $extractedNs = [];
        preg_match('/^<object([^\>]*)>/', $raw, $extractedNs);
        $attributes = [];
        preg_match_all(
            '/(^| )([^"]*"?[^"]*"|[^\']*\'?[^\']*\')/',
            $extractedNs[1], $attributes
        );
        $extractedAttributes = '';
        foreach ($attributes[0] as $attribute) {
            $attribute = trim($attribute);
            // if $attribute appears in xml, remove it:
            if (!strstr($record, $attribute)) {
                $extractedAttributes = ($extractedAttributes == '') ?
                    $attribute : $extractedAttributes . ' ' . $attribute;
            }
        }
        return $extractedAttributes;
    }
}

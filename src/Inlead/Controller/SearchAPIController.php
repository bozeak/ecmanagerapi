<?php

namespace Inlead\Controller;

use Exception;
use Inlead\Formatter\MarcRecordFormatter;
use Inlead\Search\Transformer\Query;
use Laminas\Config\Config;
use Laminas\EventManager\EventInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use SerialsSolutions\Summon\Laminas;
use stdClass;
use VuFind\Search\EmptySet\Results;
use VuFind\Search\SearchRunner;
use VuFindApi\Controller\ApiInterface;
use VuFindApi\Controller\ApiTrait;
use VuFindApi\Formatter\FacetFormatter;
use VuFindApi\Formatter\RecordFormatter;

class SearchAPIController extends \VuFind\Controller\AbstractSearch implements ApiInterface
{
    use ApiTrait;

    /**
     * Record formatter
     *
     * @var MarcRecordFormatter
     */
    protected $recordFormatter;

    /**
     * Permission required for the record endpoint
     *
     * @var string
     */
    protected $recordAccessPermission = 'access.api.Record';

    /**
     * Permission required for the search endpoint
     *
     * @var string
     */
    protected $searchAccessPermission = 'access.api.Search';

    /**
     * Default record fields to return if a request does not define the fields
     *
     * @var array
     */
    protected $defaultRecordFields = [];

    /**
     * Max limit of search results in API response (default 100);
     *
     * @var int
     */
    protected $maxLimit = 100;
    /**
     * @var FacetFormatter
     */
    protected $facetFormatter;

    public $marcOutputFormat = false;

    protected $marcFields = [];

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm Service manager
     * @param MarcRecordFormatter     $rf Record formatter
     * @param FacetFormatter          $ff Facet formatter
     */
    public function __construct(ServiceLocatorInterface $sm, MarcRecordFormatter $rf,
                                FacetFormatter $ff
    ) {
        parent::__construct($sm);
        $this->recordFormatter = $rf;
        $this->facetFormatter = $ff;
        foreach ($rf->getRecordFields() as $fieldName => $fieldSpec) {
            if (!empty($fieldSpec['vufind.default'])) {
                $this->defaultRecordFields[] = $fieldName;
            }
        }

        // Load configurations from the search options class:
        $settings = $sm->get(\VuFind\Search\Options\PluginManager::class)
            ->get($this->searchClassId)->getAPISettings();

        // Apply all supported configurations:
        $configKeys = [
            'recordAccessPermission', 'searchAccessPermission', 'maxLimit'
        ];
        foreach ($configKeys as $key) {
            if (isset($settings[$key])) {
                $this->$key = $settings[$key];
            }
        }

        if ($this->getRequest()->getQuery()->get('outputFormat') == 'marcjson') {
            $this->marcOutputFormat = true;
        }

        // Getting shown marc fields.
        $marcFields = $this->getConfig('marcfields');
        $rawMarcFields = $marcFields->get('Results')->toArray();
        foreach ($rawMarcFields as $key => $rawMarcField) {
            if (preg_match('/:/', $rawMarcField)) {
                $explode = explode(':', $rawMarcField);
                if (isset($explode[1])) {
                    $codes = [];
                    if (preg_match('/,/', $explode[1])) {
                        $explode_comma = explode(',', $explode[1]);
                        foreach ($explode_comma as $item) {
                            $codes[] = $item;
                        }
                    }
                    else {
                        $codes[] = $explode[1];
                    }
                    $ret = ['tag' => $explode[0], 'codes' => $codes];
                    $this->marcFields[$key] = $ret;
                }
            }
            else {
                $this->marcFields[$key] = ['tag' => $rawMarcField];
            }
        }
    }

    /**
     * Record action
     *
     * @return \Laminas\Http\Response
     */
    public function recordAction()
    {
        // Disable session writes
        $this->disableSessionWrites();

        $this->determineOutputMode();

        if ($result = $this->isAccessDenied($this->recordAccessPermission)) {
            return $result;
        }

        $request = $this->getRequest()->getQuery()->toArray()
            + $this->getRequest()->getPost()->toArray();

        if (!isset($request['id'])) {
            return $this->output([], self::STATUS_ERROR, 400, 'Missing id');
        }

        $loader = $this->serviceLocator->get(\VuFind\Record\Loader::class);
        try {
            if (is_array($request['id'])) {
                $results = $loader->loadBatchForSource(
                    $request['id'],
                    $this->searchClassId
                );
            } else {
                $results[] = $loader->load($request['id'], $this->searchClassId);
            }
        } catch (\Exception $e) {
            return $this->output(
                [], self::STATUS_ERROR, 400,
                'Error loading record'
            );
        }

        $response = [
            'resultCount' => count($results)
        ];
        $requestedFields = $this->getFieldList($request);

        if ($this->marcOutputFormat) {
            $requestedFields = ['id', 'fullRecord'];
        }

        if ($records = $this->recordFormatter->format($results, $requestedFields, $this->marcOutputFormat, $this->marcFields)) {
            $response['records'] = $records;
        }

        return $this->output($response, self::STATUS_OK);
    }

    /**
     * @return bool|\Laminas\Http\Response
     * @throws Exception
     */
    public function searchAction()
    {
        $this->disableSessionWrites();

        $this->determineOutputMode();

        if ($result = $this->isAccessDenied($this->searchAccessPermission)) {
            return $result;
        }

        // Send both GET and POST variables to search class:
        $request = $this->getRequest()->getQuery()->toArray()
            + $this->getRequest()->getPost()->toArray();

        if (isset($request['limit'])
            && (!ctype_digit($request['limit'])
                || $request['limit'] < 0 || $request['limit'] > $this->maxLimit)
        ) {
            return $this->output([], self::STATUS_ERROR, 400, 'Invalid limit');
        }

        // Sort by relevance by default
        if (!isset($request['sort'])) {
            $request['sort'] = 'relevance';
        }

        $mapping = $this->getConfig('DCSolrMapping');
        if (isset($request['lookfor'])) {
            $request['lookfor'] = (new Query($request['lookfor']))->transform($mapping->get('Mapping')->toArray()) ?? $request['lookfor'];
        }

        $requestedFields = $this->getFieldList($request);

        $facetConfig = $this->getConfig('facets');
        $hierarchicalFacets = isset($facetConfig->SpecialFacets->hierarchical)
            ? $facetConfig->SpecialFacets->hierarchical->toArray()
            : [];

        /** @var Config $defaultFacets */
        $defaultFacets = $facetConfig->get('Results');
        $defaultFacets = $defaultFacets->toArray();

        $request['facet'] = isset($request['facet']) ? array_merge($request['facet'], array_keys($defaultFacets)) : array_keys($defaultFacets);
        $requestedFacets = $request['facet'] ?? array_keys($defaultFacets);


        /** @var SearchRunner $runner */
        $runner = $this->serviceLocator->get(SearchRunner::class);

        try {
            $results = $runner->run(
                $request,
                $this->searchClassId,
                function ($runner, $params, $searchId) use (
                    $hierarchicalFacets, $request, $requestedFields
                ) {
                    foreach ($request['facet'] ?? []
                             as $facet
                    ) {
                        if (!isset($hierarchicalFacets[$facet])) {
                            $params->addFacet($facet);
                        }
                    }
                    if ($requestedFields) {
                        $limit = $request['limit'] ?? 20;
                        $params->setLimit($limit);
                    } else {
                        $params->setLimit(0);
                    }
                }
            );
        } catch (Exception $e) {
            return $this->output([], self::STATUS_ERROR, 400, $e->getMessage());
        }

        // If we received an EmptySet back, that indicates that the real search
        // failed due to some kind of syntax error, and we should display a
        // warning to the user; otherwise, we should proceed with normal post-search
        // processing.
        if ($results instanceof Results) {
            return $this->output([], self::STATUS_ERROR, 400, 'Invalid search');
        }

        $response = ['resultCount' => $results->getResultTotal()];

        $processMarc = false;
        if (isset($request['outputFormat']) && $request['outputFormat'] === 'marcjson') {
            $processMarc = true;
            $requestedFields = ['id', 'fullRecord'];
        }

        $records = $this->recordFormatter->format(
            $results->getResults(), $requestedFields, $processMarc, $this->marcFields
        );

        if ($records) {
            $response['records'] = $records;
        }

        $hierarchicalFacetData = $this->getHierarchicalFacetData(
            array_intersect($requestedFacets, $hierarchicalFacets)
        );
        $facets = $this->facetFormatter->format(
            $request, $results, $hierarchicalFacetData
        );
        if ($facets) {
            $response['facets'] = $facets;
        }

        return $this->output($response, self::STATUS_OK);
    }

    public function getSwaggerSpecFragment()
    {
        // TODO: Implement getSwaggerSpecFragment() method.
    }

    /**
     * Get field list based on the request
     *
     * @param array $request Request params
     *
     * @return array
     */
    protected function getFieldList($request)
    {
        $fieldList = [];
        if (isset($request['field'])) {
            if (!empty($request['field']) && is_array($request['field'])) {
                $fieldList = $request['field'];
            }
        } else {
            $fieldList = $this->defaultRecordFields;
        }
        return $fieldList;
    }

    /**
     * Get hierarchical facet data for the given facet fields
     *
     * @param array $facets Facet fields
     *
     * @return array
     */
    protected function getHierarchicalFacetData($facets)
    {
        if (!$facets) {
            return [];
        }
        $results = $this->getResultsManager()->get('Solr');
        $params = $results->getParams();
        foreach ($facets as $facet) {
            $params->addFacet($facet, null, false);
        }
        $params->initFromRequest($this->getRequest()->getQuery());

        $facetResults = $results->getFullFieldFacets($facets, false, -1, 'count');

        $facetHelper = $this->serviceLocator
            ->get(\VuFind\Search\Solr\HierarchicalFacetHelper::class);

        $facetList = [];
        foreach ($facets as $facet) {
            if (empty($facetResults[$facet]['data']['list'])) {
                $facetList[$facet] = [];
                continue;
            }
            $facetList[$facet] = $facetHelper->buildFacetArray(
                $facet,
                $facetResults[$facet]['data']['list'],
                $results->getUrlQuery(),
                false
            );
        }

        return $facetList;
    }
}

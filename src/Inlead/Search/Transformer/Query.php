<?php

namespace Inlead\Search\Transformer;

use Inlead\Services\SearchQueryLexer;
use Inlead\Services\SearchQueryParser;

/**
 * Class Query
 * @package Inlead\Search\Transformer
 */
class Query
{
    /**
     * @var
     */
    protected $string;

    protected $conditions = [
        'and' => 'and',
        'or' => 'or',
        'not' => 'not',
    ];

    protected $operands = [
        '=' => '=',
        '>' => '>',
        '<' => '<',
        'any' => 'any',
    ];

    /**
     * Query constructor.
     * @param $string
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    /**
     * Transform request query.
     *
     * @param array $mapping
     *
     * @return string|null
     */
    public function transform(array $mapping)
    {

//        \bdc\.\w+\b

        $lexer = new SearchQueryLexer();
        $ast = new SearchQueryParser($lexer);
        $parse = $ast->parse($this->string);

        $a = 1;
//        $conditions = implode(' | ', $this->conditions);
//        preg_match("/ $conditions /", $this->string, $matchedConditions);
//
//        $asda = preg_split("/ $conditions /", $this->string);
//
//        $basda = [];
//        $operands = implode(' | ', $this->operands);
//        foreach ($asda as $item) {
//            $basda[] = preg_split("/ $operands /", $item);
//        }
//
//        $a = 1;
        $match = preg_match('/dc./', $this->string);



        if ($match) {
            $split = explode(' ', $this->string);

            foreach ($split as $key => $item) {
                if (in_array(trim($item), $this->conditions)) {
                    continue;
                }

                $item = str_replace('dc.', '', $item);

                if (preg_match('/=/', $item)) {
                    [$field, $search] = explode('=', $item);
                } else {
                    unset($field);
                }

                if (!empty($field) && $mapped = $mapping[$field]) {
                    $solrFields = explode(', ', $mapped);

                    $mapSearch = array_map(function ($i) use ($search) {
                        return $i . ":" . $search;
                    }, $solrFields);

                    $queryBlock = implode(' OR ', $mapSearch);
                    $split[$key] = '(' . $queryBlock . ')';
                }
                unset($mapped, $field);
            }

            return implode(' ', $split);
        }
    }
}

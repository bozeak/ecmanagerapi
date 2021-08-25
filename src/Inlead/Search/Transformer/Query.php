<?php

namespace Inlead\Search\Transformer;

use Inlead\Query\AST\Node;
use Inlead\Query\AST\SimpleQueryNode;
use Inlead\Query\Lexer;
use Inlead\Query\Parser;

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

    /**
     * @var
     */
    protected $mapping;

    /**
     * Query constructor.
     * @param $string
     * @param $mapping
     */
    public function __construct($string, $mapping)
    {
        $this->string = $string;
        $this->mapping = $mapping;
    }

    /**
     * Transform request query.
     *
     * @return string|null
     */
    public function transform(): ?string
    {
        $lexer = new Lexer();
        $ast = new Parser($lexer);
        $parsed = $ast->parse($this->string);

        if (!$parsed instanceof Node) {
            return $this->string;
        }
        return $this->buildString($parsed);
    }

    /**
     * @param $parsed
     * @return string
     */
    protected function buildString($parsed): string
    {
        $ret = [];
        foreach ($parsed->nodes as $node) {
            if ($node instanceof SimpleQueryNode) {
                $ret[] = $this->toString($node);
            }

            if ($node instanceof Node) {
                $ret[] = $this->buildTerminals($node);
            }
        }

        return implode(" " . strtoupper($parsed->operator) . " ", $ret);
    }

    /**
     * @param $node
     * @return string
     */
    public function toString($node): string
    {
        if ($node instanceof Node) {
            return $this->buildTerminals($node);
        }
        $identifier = $node->getIdentifier()->getValue();
        $mapping = $this->processMapping();
        if (isset($mapping[$identifier])) {
            $variants = [];
            foreach ($mapping[$identifier] as $item) {
                $variants[] = $item . $node->getOperator()->getValue() . $node->getOperand()->getValue();
            }

            $return = implode(" OR ", $variants);
            return sprintf("(%s)", $return);
        }

        return $node->getIdentifier()->getValue() . $node->getOperator()->getValue() . $node->getOperand()->getValue();
    }

    /**
     * @param Node $nodes
     * @return string
     */
    private function buildTerminals(Node $nodes): string
    {
        $ret = [];
        foreach ($nodes->nodes as $node) {
            $ret[] = $this->toString($node);
        }

        $string = implode(" " . strtoupper($nodes->operator) . " ", $ret);

        if ($nodes->operator === 'or') {
            $string = sprintf("(%s)", $string);
        }
        return $string;
    }

    /**
     * @return array
     */
    private function processMapping(): array
    {
        $map = [];
        foreach ($this->mapping as $field => $items) {
            $map[$field] = explode(', ', $items);
        }

        return $map;
    }
}

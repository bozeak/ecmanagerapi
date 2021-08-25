<?php

namespace Inlead\Query\AST;

class Node
{
    const OPERATOR_AND = 'and';
    const OPERATOR_OR = 'or';
    const OPERATOR_NOT = 'not';
    /**
     * Node constructor.
     *
     * @param string $operator
     *   Node children operator.
     * @param array $childNodes
     *   Child nodes.
     */
    public function __construct($operator, array $childNodes)
    {
        $this->operator = in_array($operator, [self::OPERATOR_AND, self::OPERATOR_OR]) ? $operator : self::OPERATOR_AND;
        $this->nodes = $childNodes;
    }
}

<?php

namespace Inlead\Query\AST;

class SimpleQueryNode
{
    /** @var IdentifierNode */
    protected $identifier;

    /** @var OperatorNode */
    protected $operator;

    /** @var IdentifierNode */
    protected $operand;

    /**
     * SimpleQuery constructor.
     *
     * @param IdentifierNode $identifier
     * @param OperatorNode $operator
     * @param IdentifierNode $operand
     */
    public function __construct(IdentifierNode $identifier, OperatorNode $operator, IdentifierNode $operand)
    {
        $this->identifier = $identifier;
        $this->operator = $operator;
        $this->operand = $operand;
    }

    /**
     * @return IdentifierNode
     */
    public function getIdentifier(): IdentifierNode
    {
        return $this->identifier;
    }

    /**
     * @return OperatorNode
     */
    public function getOperator(): OperatorNode
    {
        return $this->operator;
    }

    /**
     * @return Node
     */
    public function getOperand(): IdentifierNode
    {
        return $this->operand;
    }
}

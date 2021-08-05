<?php


namespace Inlead\Ast;


use Inlead\Ast\Walker\TreeWalkerInterface;

class Node extends AbstractNode implements TransformableNodeInterface
{

    public function __construct($operator, array $childNodes)
    {
        $this->operator = in_array($operator, [self::OPERATOR_AND, self::OPERATOR_OR]) ? $operator : self::OPERATOR_AND;
        $this->nodes = $childNodes;
    }

    public function transform(TreeWalkerInterface $walker)
    {
        $walker->transform($this);
    }
}

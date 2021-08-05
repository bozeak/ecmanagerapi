<?php


namespace Inlead\Ast;


use Inlead\Ast\Walker\TreeWalkerInterface;

interface TransformableNodeInterface
{
    public function transform(TreeWalkerInterface $walker);

}

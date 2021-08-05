<?php


namespace Inlead\Ast\Walker;


use Inlead\Ast\NodeInterface;

interface TreeWalkerInterface
{

    public function transform(NodeInterface $node);

}

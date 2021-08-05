<?php

namespace Inlead\Ast;


interface NodeInterface
{
    public function getOperator();

    public function getNodes();

    public function appendChild($child);

}

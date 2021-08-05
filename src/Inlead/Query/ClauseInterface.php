<?php


namespace Inlead\Query;


interface ClauseInterface
{

    public function getOperator();

    public function getField();

    public function getValue();
}

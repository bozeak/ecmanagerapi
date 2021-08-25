<?php


namespace Inlead\Query\AST;


class IdentifierNode
{
    /** @var string */
    protected $value;

    /**
     * IdentifierNode constructor.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}

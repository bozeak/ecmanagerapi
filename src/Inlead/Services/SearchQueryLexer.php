<?php


namespace Inlead\Services;


use Doctrine\Common\Lexer\AbstractLexer;

class SearchQueryLexer extends AbstractLexer
{

    public const EXPRESSION_START = 1;

    public const EXPRESSION_END = 2;

    public const COMPARE = 10;

    public const QUOTE = 20;

    public const EQUALS = 21;

    public const IDENTIFIER = 100;

    /**
     * {@inheritDoc}
     */
    protected function getCatchablePatterns(): array
    {
        return [
            '\[[a-z]+\]',
            '[\^\$a-z._0-9- |\p{L}]+',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function getModifiers(): string
    {
        return 'iu';
    }

    /**
     * {@inheritDoc}
     */
    protected function getType(&$value)
    {
        switch (strtolower(trim($value))) {
            case '(':
                $type = self::EXPRESSION_START;
                break;
            case ')':
                $type = self::EXPRESSION_END;
                break;
//            case '=':
//            case '[eq]':
//            case '[regex]':
//            case '[in]':
//                $type = self::COMPARE;
//                break;
            case '"':
                $type = self::QUOTE;
                break;
            case '=':
//            case ':':
                $type = self::EQUALS;
                break;
            default:
                $type = self::IDENTIFIER;
        }

        return $type;
    }
}

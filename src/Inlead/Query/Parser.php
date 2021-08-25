<?php


namespace Inlead\Query;


use Doctrine\Common\Lexer\AbstractLexer;
use Exception;
use Inlead\Query\AST\IdentifierNode;
use Inlead\Query\AST\Node;
use Inlead\Query\AST\OperatorNode;
use Inlead\Query\AST\SimpleQueryNode;
use RuntimeException;

class Parser
{

    private $lexer;

    public function __construct(AbstractLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function parse($query)
    {
        if (preg_match("/[=]/", $query)) {
            $this->lexer->setInput($query);
            $this->lexer->moveNext();
            return $this->buildAST();
        }

        return $query;
    }

    function buildAST()
    {
        $operator = null;

        $childNodes[] = $this->simpleQueryFactor();

        while ($this->lexer->lookahead && $this->lexer->isNextToken(Lexer::T_IDENTIFIER) && $_operator = $this->buildOperator()) {
            if (!$operator) {
                $operator = $_operator;
            }
            $childNodes[] = $this->simpleQueryFactor();
        }

        return new Node($operator, $childNodes);
    }

    public function simpleQueryFactor()
    {
        $lookaheadType = $this->lexer->lookahead['type'];
        if ($lookaheadType === Lexer::T_OPEN_PARENTHESIS) {
            $this->lexer->moveNext();
            $expr = $this->buildAST();
            $this->match(Lexer::T_CLOSE_PARENTHESIS);
            return $expr;
        }

        $identifier = $this->identifierExpression();
        $operator = $this->operatorExpression();
        $a = 1;

        switch ($operator->getValue()) {
            case '>':
            case '>=':
            case '<':
            case '=<':
                // These can only be numbers or dates, not strings
                $operand = $this->numberOrDateExpression();
                break;
            case '=':
            case '!=':
            default:
                $operand = $this->operandExpression();
                break;
        }

        return new SimpleQueryNode($identifier, $operator, $operand);
    }

    public function match($token)
    {
        $lookaheadType = $this->lexer->lookahead['type'] ?? null;

        // Short-circuit on first condition, usually types match
        if ($lookaheadType === $token) {
            $this->lexer->moveNext();

            return;
        }

        // If parameter is not identifier (1-99) must be exact match
        if ($token < Lexer::T_IDENTIFIER) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        // If parameter is keyword (200+) must be exact match
        if ($token > Lexer::T_IDENTIFIER) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        // If parameter is T_IDENTIFIER, then matches T_IDENTIFIER (100) and keywords (200+)
        if ($token === Lexer::T_IDENTIFIER && $lookaheadType < Lexer::T_IDENTIFIER) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        $this->lexer->moveNext();
    }

    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $tokenPos = $token['position'] ?? '-1';

        $message = sprintf('line 0, col %d: Error: ', $tokenPos);
        $message .= $expected !== '' ? sprintf('Expected %s, got ', $expected) : 'Unexpected ';
        $message .= $this->lexer->lookahead === null ? 'end of string.' : sprintf("'%s'", $token['value']);

        throw new Exception($message);
    }

    /**
     * identifier := [A-Z0-9_.]+
     *
     * @return IdentifierNode
     */
    public function identifierExpression(): IdentifierNode
    {
        $this->match(Lexer::T_IDENTIFIER);
        $identifier = $this->lexer->token['value'];

        return new IdentifierNode($identifier);
    }

    public function operatorExpression(): OperatorNode
    {
        $operator = $this->lexer->lookahead['value'];
        switch ($operator) {
            case '=':
                $this->match(Lexer::T_EQUALS);
                return new OperatorNode(':');
                break;

            case '>':
                $this->match(Lexer::T_GREATER_THAN);
                break;

            case '<':
                $this->match(Lexer::T_LOWER_THAN);
                break;

            default:
                break;
        }
        $operator = $this->lexer->token['value'];

        return new OperatorNode($operator);
    }

    public function operandExpression(): IdentifierNode
    {
        $this->match(Lexer::T_IDENTIFIER);
        $operand = $this->lexer->token['value'];

        return new IdentifierNode($operand);
    }

    private function buildOperator()
    {

        $this->match(Lexer::T_IDENTIFIER);

        $token = $this->lexer->token;
        $operatorValue = trim(strtolower($token['value']));
        if (!in_array($operatorValue, [Node::OPERATOR_OR, Node::OPERATOR_AND])) {
            $position = isset($token['position']) ? $token['position'] : -1;
            $error = 'Expected a valid operator, found "' . $operatorValue . '"';
            $error .= ' at position "' . $position . '".';

            throw new RuntimeException($error);
        }

        return $operatorValue;
    }

    private function numberOrDateExpression()
    {
        $this->match(Lexer::T_INTEGER);
        $operand = $this->lexer->token['value'];

        return new IdentifierNode($operand);
    }
}

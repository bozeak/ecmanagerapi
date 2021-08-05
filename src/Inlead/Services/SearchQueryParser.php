<?php


namespace Inlead\Services;


use Doctrine\Common\Lexer\AbstractLexer;
use Inlead\Ast\AbstractNode;
use Inlead\Ast\Node;
use Inlead\Query\Clause;

class SearchQueryParser
{

    /**
     * @var \Doctrine\Common\Lexer\AbstractLexer
     */
    private $lexer;

    public function __construct(AbstractLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function parse($input)
    {
        $this->lexer->setInput($input);
        $this->lexer->moveNext();
        
        return $this->buildAST();
    }

    private function buildAST()
    {
        $operator = null;
        $childrenNodes[] = $this->buildExpression();

        while ($this->lexer->lookahead && $this->lexer->isNextToken(SearchQueryLexer::IDENTIFIER)  && $_operator = $this->buildOperator()) {
            if (!$operator) {
                $operator = $_operator;
            }

            $childrenNodes[] = $this->buildExpression();
        }


        // @todo Return something
        return new Node($operator, $childrenNodes);
    }

    private function buildExpression()
    {
//        $this->tokenMatches(SearchQueryLexer::EXPRESSION_START);

        $operator = null;
        $childNodes[] = $this->buildClause();

        // We simply keep building clauses if we encounter an operator at the end.
        while ($this->lexer->lookahead && $this->lexer->isNextToken(SearchQueryLexer::IDENTIFIER) && $_operator = $this->buildOperator()) {
            if (!$operator) {
                $operator = $_operator;
            }
            $childNodes[] = $this->buildClause();
        }

//        $this->tokenMatches(SearchQueryLexer::EXPRESSION_END);

        return new Node($operator, $childNodes);
    }

    private function buildOperator()
    {
        $this->tokenMatches(SearchQueryLexer::IDENTIFIER);

        $token = $this->lexer->token;
        $operatorValue = trim(strtolower($token['value']));
        if (!in_array($operatorValue, [AbstractNode::OPERATOR_OR, AbstractNode::OPERATOR_AND])) {
            $position = isset($token['position']) ? $token['position'] : -1;
            $error = 'Expected a valid operator, found "' . $operatorValue . '"';
            $error .= ' at position "' . $position . '".';

            throw new \RuntimeException($error);
        }

        return $operatorValue;
    }

    private function tokenMatches($tokenType)
    {
        $aheadToken = $this->lexer->lookahead;
        $position = isset($aheadToken['position']) ? $aheadToken['position'] : -1;

        if (!$aheadToken || $aheadToken['type'] !== $tokenType) {
            $error = 'Expected a valid token of type "' . $this->lexer->getLiteral($tokenType) . '"';
            $error .= !$aheadToken ? ', none found' : ', found "' . $aheadToken['value'] . '"';
            $error .= ' at position "' . $position . '".';

            throw new \RuntimeException($error);
        }

        $this->lexer->moveNext();
    }

    private function buildClause()
    {
//        $this->tokenMatches(SearchQueryLexer::QUOTE);

        $this->tokenMatches(SearchQueryLexer::IDENTIFIER);
        $field = $this->lexer->token['value'];

//        $this->tokenMatches(SearchQueryLexer::COMPARE);
//        $operator = trim($this->lexer->token['value'], '[]');

        $this->tokenMatches(SearchQueryLexer::EQUALS);
        $operator = $this->lexer->token['value'];

        $this->tokenMatches(SearchQueryLexer::IDENTIFIER);
        $value = $this->lexer->token['value'];

//        $this->tokenMatches(SearchQueryLexer::QUOTE);

        return new Clause($field, $operator, $value);
    }
}

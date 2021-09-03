<?php

namespace Inlead\Search\Transformer;

use Inlead\Search\Transformer\TokenExtractor\Full;
use QueryTranslator\Languages\Galach\Generators\Common\Aggregate;
use QueryTranslator\Languages\Galach\Generators\Native;
use QueryTranslator\Languages\Galach\Generators\Native\BinaryOperator;
use QueryTranslator\Languages\Galach\Generators\Native\Group;
use QueryTranslator\Languages\Galach\Generators\Native\Phrase;
use QueryTranslator\Languages\Galach\Generators\Native\Tag;
use QueryTranslator\Languages\Galach\Generators\Native\UnaryOperator;
use QueryTranslator\Languages\Galach\Generators\Native\User;
use QueryTranslator\Languages\Galach\Generators\Native\Word;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\Tokenizer;

/**
 * Class Query
 * @package Inlead\Search\Transformer
 */
class Query
{
    /**
     * @var
     */
    protected $string;

    /**
     * @var
     */
    protected $mapping;

    /**
     * Query constructor.
     * @param $string
     * @param $mapping
     */
    public function __construct($string, $mapping)
    {
        $this->string = $string;
        $this->mapping = $mapping;
    }

    /**
     * Transform request query.
     *
     * @return string|null
     */
    public function transform(): ?string
    {
        $tokenExtractor = new Full();
        $tokenizer = new Tokenizer($tokenExtractor);
        $parser = new Parser();

        $nativeGenerator = new Native(
            new Aggregate(
                [
                    new Group(),
                    new BinaryOperator(),
                    new UnaryOperator(),
                    new Phrase(),
                    new \QueryTranslator\Languages\Galach\Generators\Native\Query(),
                    new Tag(),
                    new Word(),
                    new User(),
                ]
            )
        );

        $tokenSequence = $tokenizer->tokenize($this->string);
        $syntaxTree = $parser->parse($tokenSequence);

        return $nativeGenerator->generate($syntaxTree);
    }
}

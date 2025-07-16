<?php

namespace Mapbender\ManagerBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * YAML data transformer
 *
 * @author Christian Wygoda
 */
class YAMLDataTransformer implements DataTransformerInterface
{
    protected $levelsBeforeInline;

    public function __construct($levelsBeforeInline = 10)
    {
        $this->levelsBeforeInline = $levelsBeforeInline;
    }

    /**
     * Encodes value to Yaml string representation
     *
     * @param mixed $value
     * @return string
     */
    public function transform($value): string
    {
        $dumper = new Dumper(2);
        $result = $dumper->dump($value, $this->levelsBeforeInline, 0, true);
        return $result === 'null' ? '' : $result;
    }

    /**
     * Decodes YAML to native type
     *
     * @param string $value
     */
    public function reverseTransform($value): mixed
    {
        $parser = new Parser();
        return $parser->parse($value, true);
    }
}


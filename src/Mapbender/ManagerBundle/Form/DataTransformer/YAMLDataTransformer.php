<?php

namespace Mapbender\ManagerBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\DumpException;
use Symfony\Component\Yaml\Parser;

/**
 * YAML <-> Array data transformer
 *
 * @author Christian Wygoda
 */
class YAMLDataTransformer implements DataTransformerInterface
{
    protected $indentLevel;

    public function __construct($indentLevel = 10)
    {
        $this->indentLevel = $indentLevel;
    }

    /**
     * Transforms array to YAML
     *
     * @param array $array
     * @return string
     */
    public function transform($array)
    {
        $dumper = new Dumper();
        $dumper->setIndentation(2);

        try {
            $yaml = $dumper->dump($array, $this->indentLevel, 0, true);
        } catch (DumpException $e) {
            throw new TransformationFailedException();
        }

        return $yaml;
    }

    /**
     * Transforms YAML to array
     *
     * @param string $yaml
     * @return array
     */
    public function reverseTransform($yaml)
    {
        $parser = new Parser();

        try {
            $array = $parser->parse($yaml);
        } catch(ParseException $e) {
            throw new TransformationFailedException();
        }

        return $array;
    }
}


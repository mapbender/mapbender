<?php

namespace Mapbender\ManagerBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * YAML data transformer
 */
class YAMLDataTransformer implements DataTransformerInterface
{
    public function __construct(
        protected int $levelsBeforeInline = 10,
        protected bool $jsonEncode = false,
    )
    {
    }

    /**
     * Encodes value to Yaml string representation
     *
     * @param mixed $value
     * @return string
     */
    public function transform($value): string
    {
        if ($this->jsonEncode && is_string($value) && json_validate($value)) {
            $value = json_decode($value, true);
        }
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
        $parsed = $parser->parse($value, true);
        return $this->jsonEncode ? json_encode($parsed) : $parsed;
    }
}


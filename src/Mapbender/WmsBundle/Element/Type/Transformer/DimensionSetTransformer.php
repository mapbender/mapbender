<?php

namespace Mapbender\WmsBundle\Element\Type\Transformer;

use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;

class DimensionSetTransformer implements DataTransformerInterface
{
    /** @var DimensionInst[] */
    protected $instances;

    /**
     * @param DimensionInst[] $dimensionInstances
     */
    public function __construct($dimensionInstances)
    {
        $this->instances = $dimensionInstances;
    }

    public function transform($value)
    {
        if ($value && !empty($value['dimension'])) {
            if (is_object($value['dimension']) && ($value['dimension'] instanceof DimensionInst)) {
                $value['dimension'] = $value['dimension']->getConfiguration();
            }
            if (is_array($value['dimension'])) {
                $value['dimension'] = json_encode($value['dimension']);
            }
        }
        return $value;
    }

    public function reverseTransform($value)
    {
        if ($value && !empty($value['dimension'])) {
            if (is_string($value['dimension'])) {
                $value['dimension'] = json_decode($value['dimension'], true);
            }
            if (is_array($value['dimension'])) {
                $value['dimension'] = DimensionInst::fromConfiguration($value['dimension']);
            }
        }
        return $value;
    }
}

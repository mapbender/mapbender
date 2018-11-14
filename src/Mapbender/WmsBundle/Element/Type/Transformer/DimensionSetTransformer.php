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

    public static function scalarFromInstance(DimensionInst $inst)
    {
        return $inst->id . "-" . $inst->getName() . "-" . $inst->getType();
    }

    public function instanceFromScalar($value)
    {
        foreach ($this->instances as $inst) {
            if ($this->scalarFromInstance($inst) == $value) {
                return $inst;
            }
        }
        return null;
    }

    public function transform($value)
    {
        if ($value && !empty($value['group'])) {
            foreach (array_keys($value['group']) as $key) {
                $dimValue = $value['group'][$key];
                $value['group'][$key] = $this->instanceFromScalar($dimValue);
            }
        }
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
        if ($value && !empty($value['group'])) {
            foreach (array_keys($value['group']) as $key) {
                $dimValue = $value['group'][$key];
                $value['group'][$key] = $this->scalarFromInstance($dimValue);
            }
        }
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

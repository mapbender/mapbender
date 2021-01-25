<?php


namespace Mapbender\WmsBundle\Element\Type;


use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DimensionSetDimensionChoiceTransformer implements DataTransformerInterface
{
    protected $dimensionInstances;

    /**
     * @param DimensionInst[] $dimensionInstances
     */
    public function __construct($dimensionInstances)
    {
        $this->dimensionInstances = $dimensionInstances;
    }

    public function transform($values)
    {
        if (!$values) {
            return array();
        }
        if (!\is_array($values)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $instances = array();
        foreach ($values as $k => $value) {
            foreach ($this->dimensionInstances as $inst) {
                if ($this->getInstanceIdent($inst) == $value) {
                    $instances[$k] = $inst;
                    break;
                }
            }
        }
        return $instances;
    }

    public function reverseTransform($values)
    {
        if (!$values) {
            return array();
        }
        if (!\is_array($values)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $strings = array();
        foreach ($values as $k => $inst) {
            /** @var DimensionInst $inst */
            $strings[$k] = $this->getInstanceIdent($inst);
        }
        return $strings;
    }

    protected function getInstanceIdent(DimensionInst $inst)
    {
        return $inst->id . "-" . $inst->getName() . "-" . $inst->getType();
    }
}

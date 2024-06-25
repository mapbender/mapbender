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

    public function transform($value): array
    {
        if (!$value) {
            return array();
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $instances = array();
        foreach ($value as $k => $val) {
            foreach ($this->dimensionInstances as $inst) {
                if ($this->getInstanceIdent($inst) == $val) {
                    $instances[$k] = $inst;
                    break;
                }
            }
        }
        return $instances;
    }

    public function reverseTransform($value): array
    {
        if (!$value) {
            return array();
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $strings = array();
        foreach ($value as $k => $inst) {
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

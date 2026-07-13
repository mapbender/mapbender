<?php


namespace Mapbender\WmsBundle\Element\Type;


use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DimensionSetDimensionChoiceTransformer implements DataTransformerInterface
{
    /**
     * @param DimensionInst[] $dimensionInstances
     */
    public function __construct(protected $dimensionInstances)
    {
    }

    public function transform($value): array
    {
        if (!$value) {
            return [];
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $instances = [];
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
            return [];
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $strings = [];
        foreach ($value as $k => $inst) {
            /** @var DimensionInst $inst */
            $strings[$k] = $this->getInstanceIdent($inst);
        }
        return $strings;
    }

    protected function getInstanceIdent(DimensionInst $inst): string
    {
        return $inst->id . "-" . $inst->getName() . "-" . $inst->getType();
    }
}

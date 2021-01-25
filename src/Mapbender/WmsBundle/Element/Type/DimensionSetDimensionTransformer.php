<?php


namespace Mapbender\WmsBundle\Element\Type;


use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;

class DimensionSetDimensionTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if (!$value) {
            return $value;
        }
        if (is_object($value) && ($value instanceof DimensionInst)) {
            $value = $value->getConfiguration();
        }
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return $value;
    }

    public function reverseTransform($value)
    {
        if (!$value) {
            return $value;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (is_array($value)) {
            $value = DimensionInst::fromConfiguration($value);
        }
        return $value;
    }
}

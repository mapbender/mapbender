<?php
namespace Mapbender\WmsBundle\Form\DataTransformer;

use Mapbender\CoreBundle\Utils\ArrayObject;
use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @author Paul Schmidt
 */
class DimensionTransformer implements DataTransformerInterface
{
    /**
     * Transforms an object to an array.
     *
     * @param DimensionInst $data
     * @return array a transformed object
     */
    public function transform($data)
    {
        if (!$data) {
            return array();
        }
        $arrayData = ArrayObject::objectToArray($data);
        return $arrayData;
    }

    /**
     * Transforms an array into an object
     *
     * @param array $data array with data for an object of the 'classname'
     * @return DimensionInst|string
     */
    public function reverseTransform($data)
    {
        if (!$data) {
            return "";
        }
        return ArrayObject::arrayToObject('Mapbender\WmsBundle\Component\DimensionInst', $data);
    }
}

<?php

namespace Mapbender\WmsBundle\Form\DataTransformer;

use Mapbender\CoreBundle\Utils\ArrayObject;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @author Paul Schmidt
 */
class VendorSpecificTransformer implements DataTransformerInterface
{
    /**
     * Transforms an object to an array.
     *
     * @param mixed $data object | array
     * @return array a transformed object
     */
    public function transform($data)
    {
        if (!$data) {
            return null;
        } elseif (!$data instanceof VendorSpecific) {
            throw new \RuntimeException("Unexpected type " . is_object($data) ? get_class($data) : gettype($data));
        }
        return ArrayObject::objectToArray($data);
    }

    /**
     * Transforms an array into an object
     *
     * @param array $data array with data for an object of the 'classname'
     * @return object of the 'classname'
     */
    public function reverseTransform($data)
    {
        if (null === $data) {
            return "";
        }
        return ArrayObject::arrayToObject("Mapbender\WmsBundle\Component\VendorSpecific", $data);
    }
}

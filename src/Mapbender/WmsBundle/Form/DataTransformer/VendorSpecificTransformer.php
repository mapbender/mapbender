<?php

namespace Mapbender\WmsBundle\Form\DataTransformer;

use Mapbender\WmsBundle\Component\VendorSpecific;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
        /** @var VendorSpecific $data */
        return array(
            'vstype' => $data->getVstype(),
            'name' => $data->getName(),
            'default' => $data->getDefault(),
            'hidden' => $data->getHidden(),
        );
    }

    /**
     * Transforms an array into an object
     *
     * @param array $data array with data for an object of the 'classname'
     * @return object|string
     */
    public function reverseTransform($data)
    {
        if (null === $data) {
            return "";
        }
        if (!\is_array($data)) {
            throw new TransformationFailedException("Expected an array.");
        }
        $vs = new VendorSpecific();
        // add defaults (potentially from new object constructor)
        $withDefaults = $data + $this->transform($vs);
        $vs->setVstype($withDefaults['vstype']);
        $vs->setName($withDefaults['name']);
        $vs->setDefault($withDefaults['default']);
        $vs->setHidden($withDefaults['hidden']);
        return $vs;
    }
}

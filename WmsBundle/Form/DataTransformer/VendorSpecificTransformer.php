<?php

namespace Mapbender\WmsBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Utils\ArrayObject;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ObjectIdTransformer transforms a value between different representations
 * 
 * @author Paul Schmidt
 */
class VendorSpecificTransformer implements DataTransformerInterface
{

    /**
     * Creates an instance.
     * 
     * @param ObjectManager $om an object manager
     * @param string $classname an entity class name
     */
    public function __construct()#ObjectManager $om, $classname)
    {
        $a = 0;
//        $this->om = $om;
//        $this->classname = $classname;
    }

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
        }
        if ($data instanceof VendorSpecific && is_array($data->getExtent())) {#isset($data['extent']) && is_array($data['extent']) && $data['type'] === DimensionInst::TYPE_MULTIPLE){
            if ($data->getType() === VendorSpecific::TYPE_MULTIPLE) {
                $data->setExtent(implode(',', $data->getExtent()));
            } else if ($data->getType() === VendorSpecific::TYPE_INTERVAL) {
                $data->setExtent(implode('/', $data->getExtent()));
            }
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
        if (isset($data['extent']) && !is_array($data['extent'])) {
            if($data['type'] === VendorSpecific::TYPE_MULTIPLE){
                $data['extent'] = explode(",", $data['extent']);
            } else if($data['type'] === VendorSpecific::TYPE_MULTIPLE){
                $data['extent'] = explode("/", $data['extent']);
            }
        }
        return ArrayObject::arrayToObject("Mapbender\WmsBundle\Component\VendorSpecific", $data);
    }

}

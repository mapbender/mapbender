<?php
namespace Mapbender\WmsBundle\Element\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Utils\ArrayObject;
use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ObjectIdTransformer transforms a value between different representations
 * 
 * @author Paul Schmidt
 */
class DimensionTransformer implements DataTransformerInterface
{

    /**
     * Transforms an object to an array.
     *
     * @param mixed $data object | array
     * @return array a transformed object
     */
    public function transform($data)
    {
        if (null === $data) {
            return null;
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
        if(isset($data['extent']) && is_string($data['extent'])){// && $data['type'] === DimensionInst::TYPE_MULTIPLE){
            $data['extent'] = implode("/", json_decode($data['extent']));
        }
        if(isset($data['origextent']) && is_string($data['origextent'])){// && $data['type'] === DimensionInst::TYPE_MULTIPLE){
            $data['origextent'] = implode("/", json_decode($data['origextent']));
        }
        return ArrayObject::arrayToObject("Mapbender\WmsBundle\Component\DimensionInst", $data);
    }

}

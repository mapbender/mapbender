<?php
namespace Mapbender\CoreBundle\Form\DataTransformer;

use Mapbender\CoreBundle\Utils\ArrayObject;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ObjectArrayTransformer transforms a value between different representations
 * 
 * @author Paul Schmidt
 */
class ObjectArrayTransformer implements DataTransformerInterface
{

    /**
     * @var string a class name
     */
    private $className;

    /**
     * Creates an instance.
     * 
     * @param string $className an entity class name
     */
    public function __construct($className)
    {
        $this->className = $className;
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
        return ArrayObject::objectToArray($data);
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
        return ArrayObject::arrayToObject($this->className, $data);
    }

}

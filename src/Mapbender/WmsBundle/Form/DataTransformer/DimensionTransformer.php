<?php
namespace Mapbender\WmsBundle\Form\DataTransformer;

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
     * @return string[]
     */
    protected static function getExtentKeys()
    {
        return array(
            'extent',
            'origextent',
        );
    }

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
        foreach ($this->getExtentKeys() as $extentKey) {
            if (!empty($arrayData[$extentKey])) {
                $arrayData[$extentKey] = $this->transformExtent($arrayData, $arrayData[$extentKey]);
            }
        }
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
        foreach ($this->getExtentKeys() as $extentKey) {
            if (isset($data[$extentKey])) {
                $data[$extentKey] = $this->revTransformExtent($data, $data[$extentKey]);
            }
        }
        return ArrayObject::arrayToObject('Mapbender\WmsBundle\Component\DimensionInst', $data);
    }

    /**
     * @param array $data
     * @param mixed $extentValue
     * @return string|null
     */
    protected function transformExtent($data, $extentValue)
    {
        switch ($data['type']) {
            case DimensionInst::TYPE_MULTIPLE:
                return implode(',', $extentValue);
            case DimensionInst::TYPE_INTERVAL:
                return implode('/', $extentValue);
            default:
                throw new \RuntimeException("Unhandled type " . var_export($data['type'], true));
        }
    }

    /**
     * @param array $data
     * @param mixed $extentValue
     * @return string|null
     */
    protected function revTransformExtent($data, $extentValue)
    {
        switch ($data['type']) {
            case DimensionInst::TYPE_MULTIPLE:
                return explode(',', $extentValue);
            case DimensionInst::TYPE_INTERVAL:
                return explode('/', $extentValue);
            default:
                throw new \RuntimeException("Unhandled type " . var_export($data['type'], true));
        }
    }
}

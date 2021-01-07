<?php
namespace Mapbender\WmsBundle\Form\DataTransformer;

use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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

        return array(
            'origextent' => $data->getOrigextent(),
            'active' => $data->getActive(),
            'type' => $data->getType(),
            'name' => $data->getName(),
            'units' => $data->getUnits(),
            'unitSymbol' => $data->getUnitSymbol(),
            'default' => $data->getDefault(),
            'multipleValues' => $data->getMultipleValues(),
            'nearestValue' => $data->getNearestValue(),
            'current' => $data->getCurrent(),
            'extent' => $data->getExtent(),
        );
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
        if (!\is_array($data)) {
            throw new TransformationFailedException("Expected an array.");
        }
        $dimInst = new DimensionInst();
        // add defaults (potentially from new object constructor)
        $withDefaults = $data + $this->transform($dimInst);
        $dimInst->setOrigextent($withDefaults['origextent']);
        $dimInst->setActive($withDefaults['active']);
        $dimInst->setType($withDefaults['type']);
        $dimInst->setName($withDefaults['name']);
        $dimInst->setUnits($withDefaults['units']);
        $dimInst->setUnitSymbol($withDefaults['unitSymbol']);
        $dimInst->setDefault($withDefaults['default']);
        $dimInst->setMultipleValues($withDefaults['multipleValues']);
        $dimInst->setNearestValue($withDefaults['nearestValue']);
        $dimInst->setCurrent($withDefaults['current']);
        $dimInst->setExtent($withDefaults['extent']);
        return $dimInst;
    }

    /**
     * @param string $type
     * @param mixed $extentValue
     * @return string|null
     */
    protected function transformExtent($type, $extentValue)
    {
        switch ($type) {
            case DimensionInst::TYPE_MULTIPLE:
                return explode(',', $extentValue);
            case DimensionInst::TYPE_INTERVAL:
                return explode('/', $extentValue);
            default:
                throw new \RuntimeException("Unhandled type " . var_export($type, true));
        }
    }

    /**
     * @param string $type
     * @param mixed $extentValue
     * @return string|null
     */
    protected function revTransformExtent($type, $extentValue)
    {
        switch ($type) {
            case DimensionInst::TYPE_MULTIPLE:
                return implode(',', $extentValue);
            case DimensionInst::TYPE_INTERVAL:
                return implode('/', $extentValue);
            default:
                throw new \RuntimeException("Unhandled type " . var_export($type, true));
        }
    }
}

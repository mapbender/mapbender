<?php
namespace Mapbender\WmsBundle\Element\DataTransformer;

/**
 * Class ObjectIdTransformer transforms a value between different representations
 * 
 * @author Paul Schmidt
 */
class DimensionTransformer extends \Mapbender\WmsBundle\Form\DataTransformer\DimensionTransformer
{

    protected function transformExtent($data, $extentValue)
    {
        if ($extentValue) {
            return json_encode($extentValue);
        } else {
            return null;
        }
    }

    protected function revTransformExtent($data, $extentValue)
    {
        return json_decode($extentValue);
    }
}

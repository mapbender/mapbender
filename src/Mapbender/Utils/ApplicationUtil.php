<?php


namespace Mapbender\Utils;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;

class ApplicationUtil
{
    /**
     * @param Application $application
     * @return Element|null
     */
    public static function getMapElement(Application $application)
    {
        $elements = static::getMapElements($application) ?: array(null);
        return $elements[0];
    }

    /**
     * @param Application $application
     * @return Element[]
     */
    public static function getMapElements(Application $application)
    {
        $matches = array();
        foreach ($application->getElements() as $element) {
            $className = $element->getClass();
            if ($className && ClassUtil::exists($className) && \is_a($className, 'Mapbender\Component\Element\MainMapElementInterface', true)) {
                $matches[] = $element;
            }
        }
        return $matches;
    }

    /**
     * @param Application $application
     * @return Layerset[]
     */
    public static function getMapLayersets(Application $application)
    {
        // @todo: this should always be everything except overview
        // historically, manual assignment to map element is assumed
        $mapEl = static::getMapElement($application);
        $layersets = array();
        if ($mapEl) {
            $mapConfig = $mapEl->getConfiguration();
            if (!empty($mapConfig['layersets'])) {
                $layersetIds = \array_map('\strval', $mapConfig['layersets']);
            } else {
                $layersetIds = array();
            }
            foreach ($application->getLayersets() as $candidate) {
                if (\in_array(\strval($candidate->getId()), $layersetIds, true)) {
                    $layersets[] = $candidate;
                }
            }
        }
        return $layersets;
    }
}

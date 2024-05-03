<?php


namespace Mapbender\Utils;


use Mapbender\Component\Element\MainMapElementInterface;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Layerset;

class ApplicationUtil
{
    public static function getMapElement(Application $application): ?Element
    {
        $elements = static::getMapElements($application) ?: [null];
        return $elements[0];
    }

    /**
     * @return Element[]
     */
    public static function getMapElements(Application $application): array
    {
        $mapElements = [];
        foreach ($application->getElements() as $element) {
            if ($element instanceof MainMapElementInterface) $mapElements[] = $element;
        }
        return $mapElements;
    }

    /**
     * @return Layerset[]
     */
    public static function getMapLayersets(Application $application): array
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

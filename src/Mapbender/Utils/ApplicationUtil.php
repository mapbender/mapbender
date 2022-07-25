<?php


namespace Mapbender\Utils;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;

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
}

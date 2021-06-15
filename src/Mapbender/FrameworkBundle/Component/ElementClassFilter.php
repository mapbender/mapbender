<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\CoreBundle\Entity\Element;

class ElementClassFilter
{
    /**
     * Implements (curated) element class splits.
     * Currently: update legacy any-function Button into ControlButton / LinkButton
     *
     * @param Element $element
     */
    protected function prepareClass(Element $element)
    {
        if ($element->getClass() && $element->getClass() === 'Mapbender\CoreBundle\Element\Button') {
            $config = $element->getConfiguration();
            if (!empty($config['click'])) {
                $element->setClass('Mapbender\CoreBundle\Element\LinkButton');
            } else {
                $element->setClass('Mapbender\CoreBundle\Element\ControlButton');
            }
        }
    }
}

<?php

namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Entity\Element;

/**
 * ElementExtension
 */
class ElementExtension extends \Twig_Extension
{
    
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_element';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return array(
            'element_class_title' => new \Twig_SimpleFunction('element_class_title', array($this, 'element_class_title')),
        );
    }

    /**
     * 
     * @param Element $element
     * @return string
     */
    public function element_class_title($element)
    {
        $class = $element->getClass();
        if(class_exists($class)) {
            return $class::getClassTitle();
        }
    }
}


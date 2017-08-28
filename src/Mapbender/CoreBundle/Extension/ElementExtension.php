<?php

namespace Mapbender\CoreBundle\Extension;

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
            'element_class_title' => new \Twig_SimpleFunction(
                'element_class_title',
                array($this,
                      'element_class_title'
                )
            )
        );
    }

    /**
     * 
     * @param type $element
     * @return type
     */
    public function element_class_title($element)
    {
        $class = $element->getClass();
        if(class_exists($class)) {
            return $class::getClassTitle();
        }
    }
}


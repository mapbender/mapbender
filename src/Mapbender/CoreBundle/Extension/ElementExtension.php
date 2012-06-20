<?php

namespace Mapbender\CoreBundle\Extension;

class ElementExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'mapbender_element';
    }

    public function getFunctions()
    {
        return array(
            'element_class_title' => new \Twig_Function_Method($this, 'element_class_title'));
    }

    public function element_class_title($element)
    {
        $class = $element->getClass();
        return $class::getClassTitle();
    }
}


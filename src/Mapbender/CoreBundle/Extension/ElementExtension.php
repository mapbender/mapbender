<?php

namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Component\ElementCompatibilityChecker;
use Mapbender\CoreBundle\Entity\Element;

/**
 * ElementExtension
 */
class ElementExtension extends \Twig_Extension
{

    /** @var ElementCompatibilityChecker */
    protected $compatibilityChecker;

    /**
     * @param ElementCompatibilityChecker $compatiblityChecker
     */
    public function __construct(ElementCompatibilityChecker $compatiblityChecker)
    {
        $this->compatibilityChecker = $compatiblityChecker;
    }

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
        $adjustedClass = $this->compatibilityChecker->getAdjustedElementClassName($class);
        if (class_exists($adjustedClass)) {
            return $adjustedClass::getClassTitle();
        }
    }
}


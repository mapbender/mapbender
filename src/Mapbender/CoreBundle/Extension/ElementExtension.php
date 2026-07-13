<?php

namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * ElementExtension
 */
class ElementExtension extends AbstractExtension
{

    /**
     * @param ElementFilter $elementFilter
     */
    public function __construct(protected ElementFilter $elementFilter)
    {
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'mapbender_element';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            'element_class_title' => new TwigFunction('element_class_title', $this->element_class_title(...)),
            'element_default_title' => new TwigFunction('element_default_title', $this->element_default_title(...)),
            'element_title' => new TwigFunction('element_title', $this->element_title(...)),
            'is_typeof_element_disabled' => new TwigFunction('is_typeof_element_disabled', $this->is_typeof_element_disabled(...)),
        ];
    }

    /**
     *
     * @param Element $element
     * @return string|null
     */
    public function element_class_title(Element $element)
    {
        return $this->elementFilter->getClassTitle($element);
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function element_title($element)
    {
        if ($title = $element->getTitle()) {
            return $title;
        } else {
            return $this->element_default_title($element);
        }
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function element_default_title(Element $element)
    {
        return $this->elementFilter->getDefaultTitle($element);
    }

    public function is_typeof_element_disabled(Element $element)
    {
        return $this->elementFilter->isDisabledType($element);
    }
}

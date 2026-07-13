<?php


namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\Renderer\ElementMarkupRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementMarkupExtension extends AbstractExtension
{
    /**
     * @param ElementMarkupRenderer $markupRenderer
     */
    public function __construct(protected ElementMarkupRenderer $markupRenderer)
    {
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'mapbender_element_markup';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            'element_visibility_class' => new TwigFunction('element_visibility_class', $this->element_visibility_class(...)),
            'element_markup' => new TwigFunction('element_markup', $this->element_markup(...)),
            'find_icon' => new TwigFunction('find_icon', $this->find_icon(...))
        ];
    }

    /**
     * @param Element $element
     * @return string
     */
    public function element_markup(Element $element)
    {
        return $this->markupRenderer->renderElements([$element]);
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function element_visibility_class(Element $element)
    {
        return $this->markupRenderer->getElementVisibilityClass($element);
    }

    /**
     * @param Element $element
     * @return string
     */
    public function find_icon($element, $additionalClass = '')
    {
        return $this->markupRenderer->getIcon($element, $additionalClass);
    }
}

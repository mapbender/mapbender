<?php


namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\Renderer\ElementMarkupRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ElementMarkupExtension extends AbstractExtension
{
    /** @var ElementMarkupRenderer */
    protected $markupRenderer;

    /**
     * @param ElementMarkupRenderer $markupRenderer
     */
    public function __construct(ElementMarkupRenderer $markupRenderer)
    {
        $this->markupRenderer = $markupRenderer;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'mapbender_element_markup';
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return array(
            'element_visibility_class' => new TwigFunction('element_visibility_class', array($this, 'element_visibility_class')),
            'element_markup' => new TwigFunction('element_markup', array($this, 'element_markup')),
            'find_icon' => new TwigFunction('find_icon', array($this, 'find_icon'))
        );
    }

    /**
     * @param Element $element
     * @return string
     */
    public function element_markup(Element $element)
    {
        return $this->markupRenderer->renderElements(array($element));
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function element_visibility_class($element)
    {
        return $this->markupRenderer->getElementVisibilityClass($element);
    }

        /**
     * @param Element $element
     * @return string
     */
    public function find_icon($element)
    {
        return $this->markupRenderer->getIcon($element);
    }
}

<?php


namespace Mapbender\CoreBundle\Extension;

use Mapbender\CoreBundle\Component;
use Mapbender\CoreBundle\Entity;
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
    public function getFunctions()
    {
        return array(
            'element_visibility_class' => new TwigFunction('element_visibility_class', array($this, 'element_visibility_class')),
            'element_markup' => new TwigFunction('element_markup', array($this, 'element_markup')),
        );
    }

    /**
     * @param Entity\Element|Component\Element $element
     * @return string
     */
    public function element_markup($element)
    {
        return $this->markupRenderer->renderElements(array($this->normalizeElementEntityArgument($element)));
    }

    /**
     * @param Entity\Element|Component\Element $element
     * @return string|null
     */
    public function element_visibility_class($element)
    {
        return $this->markupRenderer->getElementVisibilityClass($element);
    }

    /**
     * @param Entity\Element|Component\Element $element
     * @return Entity\Element
     * @throws \InvalidArgumentException
     */
    protected function normalizeElementEntityArgument($element)
    {
        if ($element instanceof Component\Element) {
            $element = $element->getEntity();
        }
        if (!$element instanceof Entity\Element) {
            throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? \get_class($element) : gettype($element));
        }
        return $element;
    }
}

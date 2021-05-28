<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;


/**
 * Default implementation for service mapbender.renderer.element_markup
 * Produces HTML markup for elements
 * Deals exclusively with Element Entity, never Component\Entity
 */
class ElementMarkupRenderer
{
    /** @var EngineInterface */
    protected $templatingEngine;
    /** @var bool */
    protected $allowResponsiveElements;
    /** @var ElementFactory */
    protected $elementFactory;

    public function __construct(EngineInterface $templatingEngine,
                                ElementFactory $elementFactory,
                                $allowResponsiveElements)
    {
        $this->templatingEngine = $templatingEngine;
        $this->elementFactory = $elementFactory;
        $this->allowResponsiveElements = $allowResponsiveElements;
    }

    /**
     * @param Element[] $elements
     * @return string
     */
    public function renderElements($elements)
    {
        $wrappers = array();
        $markupFragments = array();
        foreach ($elements as $element) {
            if (!$element instanceof Element) {
                throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? \get_class($element) : gettype($element));
            }
            $regionName = $element->getRegion();
            if (!array_key_exists($regionName, $wrappers)) {
                $wrappers[$regionName] = $this->getRegionGlue($regionName);
            }
            $wrapper = $wrappers[$regionName];
            if ($visibilityClass = $this->getElementVisibilityClass($element)) {
                $wrapper['class'] = ltrim($wrapper['class'] . ' ' . $visibilityClass);
                if (!$wrapper['tagName']) {
                    $wrapper['tagName'] = 'div';
                }
            }

            $markupFragments[] = $this->renderContent($element, $wrapper['tagName'], array_filter(array(
                'class' => $wrapper['class'],
            )));
        }
        return implode('', $markupFragments);
    }

    /**
     * @param Element[] $elements
     * @return string
     */
    public function renderFloatingElements($elements)
    {
        $markup = '';
        foreach ($elements as $element) {
            if (!$element instanceof Element) {
                throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? \get_class($element) : gettype($element));
            }
            $markup .= $this->renderContent($element, 'div', array(
                'class' => rtrim('element-wrapper ' . $this->getElementVisibilityClass($element)),
            ));
        }
        return $markup;
    }

    protected function renderContent(Element $element, $wrapperTag, $attributes)
    {
        $handlerService = $this->elementFactory->getInventory()->getHandlerService($element);
        if ($handlerService) {
            return $this->renderServiceElement($handlerService, $element, $wrapperTag, $attributes);
        } elseif (\is_a($element->getClass(), 'Mapbender\CoreBundle\Component\ElementBase\BoundSelfRenderingInterface', true)) {
            return $this->wrapTag($this->renderLegacyElement($element), $wrapperTag, $attributes);
        } else {
            throw new ElementErrorException("Don't know how to render {$element->getClass()}");
        }
    }

    /**
     * @todo: prefer interface type, add signature type hint
     * @param AbstractElementService $handlerService
     * @param Element $element
     * @param string $wrapperTag
     * @param string $attributes
     * @return string
     */
    protected function renderServiceElement($handlerService, Element $element, $wrapperTag, $attributes)
    {
        $view = $handlerService->getView($element);
        if ($view && ($view instanceof TemplateView)) {
            // @todo: unify wrapper + inherent element attributes
            $content = $this->templatingEngine->render($view->getTemplate(), $view->variables);
            return $this->wrapTag($content, $wrapperTag, $attributes);
        } else {
            throw new ElementErrorException("Don't know how to render " . get_class($view));
        }
    }

    /**
     * @param Element $element
     * @return string
     */
    protected function renderLegacyElement(Element $element)
    {
        return $this->elementFactory->componentFromEntity($element, true)->render();
    }

    /**
     * @param Element $element
     * @return string[]|null
     */
    protected function getElementWrapper(Element $element)
    {
        $visibilityClass = $this->getElementVisibilityClass($element);
        if ($visibilityClass) {
            return array(
                'tagName' => 'div',
                'class' => $visibilityClass,
            );
        } else {
            return null;
        }
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function getElementVisibilityClass(Element $element)
    {
        // Allow screenType filtering only on current map engine
        if (!$this->allowResponsiveElements || $element->getApplication()->getMapEngineCode() === Application::MAP_ENGINE_OL2) {
            return null;
        }
        switch ($element->getScreenType()) {
            case ScreenTypes::ALL:
            default:
                return null;
            case ScreenTypes::MOBILE_ONLY:
                return 'hide-screentype-desktop';
            case ScreenTypes::DESKTOP_ONLY:
                return 'hide-screentype-mobile';
        }
    }

    /**
     * @param string $content
     * @param string $tagName return $content unchanged if $tagName empty
     * @param string[] $attributes
     * @return string
     */
    protected function wrapTag($content, $tagName, $attributes)
    {
        if ($tagName) {
            $renderedAttributes = array();
            foreach ($attributes as $name => $value) {
                $renderedAttributes[] = $name . '="' . \htmlspecialchars($value) . '"';
            }
            return
                "<$tagName" . \rtrim(' ' . implode(' ', $renderedAttributes)) . '>'
                . $content
                . "</$tagName>"
            ;
        } else {
            return $content;
        }
    }

    /**
     * Detect appropriate Element markup wrapping tag for a named region.
     *
     * @param string $regionName
     * @return string[]|null
     */
    protected static function getRegionGlue($regionName)
    {
        // Legacy lenience in patterns: allow postfixes / prefixes around region names, e.g.
        // "some-custom-project-footer"
        if (false !== strpos($regionName, 'footer') || false !== strpos($regionName, 'toolbar')) {
            return array(
                'tagName' => 'li',
                'class' => 'toolBarItem',
            );
        } else {
            return array(
                'tagName' => null,
                'class' => '',
            );
        }
    }
}

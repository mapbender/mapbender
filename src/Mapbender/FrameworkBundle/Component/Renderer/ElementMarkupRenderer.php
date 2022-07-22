<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;

use Mapbender\Component\ClassUtil;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\ElementView;
use Mapbender\Component\Element\LegacyView;
use Mapbender\Component\Element\StaticView;
use Mapbender\Component\Element\TemplateView;
use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig;


/**
 * Default implementation for service mapbender.renderer.element_markup
 * Produces HTML markup for elements
 * Deals exclusively with Element Entity, never Component\Entity
 */
class ElementMarkupRenderer
{
    /** @var Twig\Environment */
    protected $templatingEngine;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var ElementInventoryService */
    protected $inventory;
    /** @var bool */
    protected $allowResponsiveElements;
    /** @var bool */
    protected $debug;

    public function __construct(Twig\Environment $templatingEngine,
                                TranslatorInterface $translator,
                                ElementInventoryService $inventory,
                                $allowResponsiveElements,
                                $debug)
    {
        $this->templatingEngine = $templatingEngine;
        $this->translator = $translator;
        $this->inventory = $inventory;
        $this->allowResponsiveElements = $allowResponsiveElements;
        $this->debug = $debug;
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
            $content = $this->renderContent($element, 'div', array());
            $markup .= $this->wrapTag($content, 'div', array(
                'class' => rtrim('element-wrapper ' . $this->getElementVisibilityClass($element)),
            ));
        }
        return $markup;
    }

    protected function renderContent(Element $element, $wrapperTag, $attributes)
    {
        try {
            $view = $this->inventory->getFrontendHandler($element)->getView($element);
            if ($view) {
                if ($view instanceof LegacyView) {
                    return $this->wrapTag($view->getContent(), $wrapperTag, $attributes);
                } else {
                    return $this->renderView($view, $wrapperTag, $attributes + array(
                        'id' => $element->getId(),
                    ));
                }
            } else {
                return '';
            }
        } catch (\Twig\Error\Error $e) {
            if ($this->debug) {
                throw $e;
            } else {
                return "<!-- "
                    . "element #{$element->getId()} failed to render with " . \htmlspecialchars($e->getMessage())
                    . " -->"
                ;
            }
        }
    }

    /**
     * @param ElementView $view
     * @param string $wrapperTag
     * @param string[] $baseAttributes
     * @return string
     */
    protected function renderView(ElementView $view, $wrapperTag, $baseAttributes)
    {
        if (!$view->cacheable) {
            $baseAttributes += array('class' => '');
            $baseAttributes['class'] = ltrim($baseAttributes['class'] . ' -js-reload-uncacheable');
        }
        $attributes = $this->prepareAttributes($view->attributes, $baseAttributes);
        if ($view instanceof TemplateView) {
            $content = $this->templatingEngine->render($view->getTemplate(), $view->variables);
        } elseif ($view instanceof StaticView) {
            $content = $view->getContent();
        } else {
            throw new \Exception("Don't know how to render " . get_class($view));
        }
        return $this->wrapTag($content, $wrapperTag ?: 'div', $attributes);
    }

    protected function prepareAttributes($viewAttributes, $baseAttributes)
    {
        $classes = array('mb-element');
        if (!empty($viewAttributes['class'])) {
            $classes[] = $viewAttributes['class'];
        }
        if (!empty($baseAttributes['class'])) {
            $classes[] = $baseAttributes['class'];
        }
        $attributes = array_replace($viewAttributes + $baseAttributes, array(
            'class' => implode(' ', array_filter($classes)),
        ));
        $translatable = array(
            'title',
            'data-title',
        );
        foreach ($translatable as $attribute) {
            if (!empty($attributes[$attribute])) {
                $attributes[$attribute] = $this->translator->trans($attributes[$attribute]);
            }
        }
        return $attributes;
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

    public function isMenuSupported(Element $element)
    {
        $handling = $this->inventory->getHandlingClassName($element);
        if (!$handling || !ClassUtil::exists($handling)) {
            return false;
        }
        if (\is_a($handling, ButtonLike::class, true)) {
            return true;
        }
        $legacyBtn = 'Mapbender\CoreBundle\Element\BaseButton';
        if (ClassUtil::exists($legacyBtn) && \is_a($handling, $legacyBtn, true)) {
            return true;
        }
        return false;
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

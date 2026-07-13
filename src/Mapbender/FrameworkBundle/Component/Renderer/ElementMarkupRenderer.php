<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;

use Twig\Environment;
use Twig\Error\Error;
use Mapbender\Component\ClassUtil;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\ElementView;
use Mapbender\Component\Element\LegacyView;
use Mapbender\Component\Element\StaticView;
use Mapbender\Component\Element\TemplateView;
use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Contracts\Translation\TranslatorInterface;
use Mapbender\FrameworkBundle\Component\IconIndex;


/**
 * Default implementation for service mapbender.renderer.element_markup
 * Produces HTML markup for elements
 * Deals exclusively with Element Entity, never Component\Entity
 */
class ElementMarkupRenderer
{
    /**
     * @param bool $allowResponsiveElements
     * @param bool $debug
     */
    public function __construct(protected Environment $templatingEngine, protected TranslatorInterface $translator, protected ElementInventoryService $inventory, protected $allowResponsiveElements, protected $debug, protected IconIndex $iconIndex)
    {
    }

    /**
     * @param Element[] $elements
     * @param bool $forceLabel force display of the element label, overriding the element's own configuration
     * @return string
     */
    public function renderElements($elements, $forceLabel = false): string
    {
        $wrappers = [];
        $markupFragments = [];
        foreach ($elements as $element) {
            if (!$element instanceof Element) {
                throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? $element::class : gettype($element));
            }
            $regionName = $element->getRegion();
            if (!array_key_exists($regionName, $wrappers)) {
                $wrappers[$regionName] = static::getRegionGlue($regionName);
            }
            $wrapper = $wrappers[$regionName];
            if ($visibilityClass = $this->getElementVisibilityClass($element)) {
                $wrapper['class'] = ltrim($wrapper['class'] . ' ' . $visibilityClass);
                if (!$wrapper['tagName']) {
                    $wrapper['tagName'] = 'div';
                }
            }
            $markupFragments[] = $this->renderContent($element, $wrapper['tagName'], array_filter([
                'class' => $wrapper['class'],
            ]), $forceLabel);
        }
        return implode('', $markupFragments);
    }

    /**
     * @param Element[] $elements
     * @return string
     */
    public function renderFloatingElements($elements): string
    {
        $markup = '';
        foreach ($elements as $element) {
            if (!$element instanceof Element) {
                throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? $element::class : gettype($element));
            }
            $content = $this->renderContent($element, 'div', []);
            $markup .= $this->wrapTag($content, 'div', [
                'class' => rtrim('element-wrapper ' . $this->getElementVisibilityClass($element)),
            ]);
        }
        return $markup;
    }

    protected function renderContent(Element $element, $wrapperTag, $attributes, $forceLabel = false)
    {
        try {
            $view = $this->inventory->getFrontendHandler($element)->getView($element);
            if ($view) {
                if ($view instanceof LegacyView) {
                    return $this->wrapTag($view->getContent(), $wrapperTag, $attributes);
                } else {
                    return $this->renderView($view, $wrapperTag, $attributes + [
                        'id' => $element->getId(),
                    ], $forceLabel);
                }
            } else {
                return '';
            }
        } catch (Error $e) {
            if ($this->debug) {
                throw $e;
            } else {
                return "<!-- "
                    . "element #{$element->getId()} failed to render"
                    . ($e->getMessage() ? (" with " . \htmlspecialchars($e->getMessage())) : '')
                    . " -->"
                ;
            }
        }
    }

    /**
     * @param ElementView $view
     * @param string $wrapperTag
     * @param string[] $baseAttributes
     * @param bool $forceLabel force display of the element label, overriding the element's own configuration
     * @return string
     */
    protected function renderView(ElementView $view, $wrapperTag, array $baseAttributes, $forceLabel = false)
    {
        if (!$view->cacheable) {
            $baseAttributes += ['class' => ''];
            $baseAttributes['class'] = ltrim($baseAttributes['class'] . ' -js-reload-uncacheable');
        }
        $attributes = $this->prepareAttributes($view->attributes, $baseAttributes);
        if ($view instanceof TemplateView) {
            if ($forceLabel) {
                $view->variables['force_menu_label'] = true;
            }
            $content = $this->templatingEngine->render($view->getTemplate(), $view->variables);
        } elseif ($view instanceof StaticView) {
            $content = $view->getContent();
        } else {
            throw new \Exception("Don't know how to render " . $view::class);
        }
        return $this->wrapTag($content, $wrapperTag ?: 'div', $attributes);
    }

    /**
     * @return mixed[]
     */
    protected function prepareAttributes(array $viewAttributes, array $baseAttributes): array
    {
        $classes = ['mb-element'];
        if (!empty($viewAttributes['class'])) {
            $classes[] = $viewAttributes['class'];
        }
        if (!empty($baseAttributes['class'])) {
            $classes[] = $baseAttributes['class'];
        }
        $attributes = array_replace($viewAttributes + $baseAttributes, [
            'class' => implode(' ', array_filter($classes)),
        ]);
        $translatable = [
            'title',
            'data-title',
        ];
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
    public function getElementVisibilityClass(Element $element): ?string
    {
        if (!$this->allowResponsiveElements) {
            return null;
        }
        return match ($element->getScreenType()) {
            ScreenTypes::MOBILE_ONLY => 'hide-screentype-desktop',
            ScreenTypes::DESKTOP_ONLY => 'hide-screentype-mobile',
            default => null,
        };
    }

    public function getIcon($element, $additionalClass = ''){

        if (!isset($element->getConfiguration()['element_icon'])) {
            return '';
        }

        $iconCode = $element->getConfiguration()['element_icon'];

        if ($this->iconIndex->isHandled($iconCode)) {
            return $this->iconIndex->getIconMarkup($iconCode, 'mb-icon ' . $additionalClass);
        }
        return '';
    }


    public function isMenuSupported(Element $element): bool
    {
        $handling = $this->inventory->getHandlingClassName($element);
        if (!$handling || !ClassUtil::exists($handling)) {
            return false;
        }
        if (\is_a($handling, ButtonLike::class, true)) {
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
            $renderedAttributes = [];
            foreach ($attributes as $name => $value) {
                $value = $value !== null ? htmlspecialchars($value) : "";
                $renderedAttributes[] = $name . '="' . $value . '"';
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
    protected static function getRegionGlue($regionName): array
    {
        // Legacy lenience in patterns: allow postfixes / prefixes around region names, e.g.
        // "some-custom-project-footer"
        if (str_contains($regionName, 'footer') || str_contains($regionName, 'toolbar')) {
            return [
                'tagName' => 'li',
                'class' => 'toolBarItem',
            ];
        } else {
            return [
                'tagName' => null,
                'class' => '',
            ];
        }
    }
}

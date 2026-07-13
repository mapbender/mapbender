<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\SketchAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;

class Sketch extends AbstractElementService
    implements ConfigMigrationInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.sketch.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.sketch.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbSketch';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbSketch.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/sketch.scss',
            ],
            'trans' => [
                'mb.core.sketch.*',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'autoOpen' => false,
            "deactivate_on_close" => true,
            "geometrytypes" => [
                "point",
                "line",
                "polygon",
                "rectangle",
                "circle",
            ],
            'colors' => [
                '#ff3333',
                '#3333ff',
                '#44ee44',
            ],
            'allow_custom_color' => true,
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return SketchAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/sketch.html.twig';
    }

    public function getClientConfiguration(Element $element): array
    {
        return array_replace($element->getConfiguration(), [
            'title' => $element->getTitle(),
            'radiusEditing' => true,
        ]);
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/sketch.html.twig');
        $view->attributes['class'] = 'mb-element-sketch';
        $view->variables['geometrytypes'] = $element->getConfiguration()['geometrytypes'];
        $view->variables['radiusEditing'] = true;
        $view->variables['dialogMode'] = !\preg_match('#sidepane|mobilepane#i', (string) $element->getRegion());
        $view->variables['colors'] = $element->getConfiguration()['colors'];
        $view->variables['allow_custom_color'] = $element->getConfiguration()['allow_custom_color'];
        return $view;
    }

    public static function updateEntityConfig(Element $entity): void
    {
        // Bridge undocumented legacy "paintstyles" to "colors"
        $config = $entity->getConfiguration();
        if (!empty($config['paintstyles']['fillColor'])) {
            $config += ['colors' => [$config['paintstyles']['fillColor']]];
        }
        unset($config['paintstyles']);
        if (isset($config['auto_activate'])) {
            $config['autoOpen'] = $config['auto_activate'];
        }
        unset($config['auto_activate']);

        if (array_key_exists('geometrytypes', $config)) {
            // Geometry Type "text" deprecated and replaced by "point" in v3.3.4
            $position = array_search('text', $config['geometrytypes']);
            if ($position !== false) {
                if (in_array('point', $config['geometrytypes'])) {
                    // do not add 'point' a second time if it already exists
                    unset($config['geometrytypes'][$position]);
                    $config['geometrytypes'] = array_values($config['geometrytypes']);
                } else {
                    // no 'point' in configuration, replace existing entry
                    $config['geometrytypes'][$position] = 'point';
                }
            }

        }

        $entity->setConfiguration($config);
    }

    public static function getDefaultIcon(): string
    {
        return 'iconEdit';
    }
}

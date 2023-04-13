<?php

namespace Mapbender\CoreBundle\Element;

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
    public static function getClassTitle()
    {
        return "mb.core.sketch.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.sketch.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbSketch';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/element/sketch.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/element/sketch.scss',
            ),
            'trans' => array(
                'mb.core.sketch.*',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'autoOpen' => false,
            "deactivate_on_close" => true,
            "geometrytypes" => array(
                "point",
                "line",
                "polygon",
                "rectangle",
                "circle",
            ),
            'colors' => array(
                '#ff3333',
                '#3333ff',
                '#44ee44',
            ),
            'allow_custom_color' => true,
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SketchAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:sketch.html.twig';
    }

    public function getClientConfiguration(Element $element)
    {
        return array_replace($element->getConfiguration(), array(
            'title' => $element->getTitle(),
            'radiusEditing' => $this->getRadiusEditing($element),
        ));
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:sketch.html.twig');
        $view->attributes['class'] = 'mb-element-sketch';
        $view->variables['geometrytypes'] = $element->getConfiguration()['geometrytypes'];
        $view->variables['radiusEditing'] = $this->getRadiusEditing($element);
        $view->variables['dialogMode'] = !\preg_match('#sidepane|mobilepane#i', $element->getRegion());
        $view->variables['colors'] = $element->getConfiguration()['colors'];
        $view->variables['allow_custom_color'] = $element->getConfiguration()['allow_custom_color'];
        return $view;
    }

    /**
     * @param Element $element
     * @return bool
     */
    protected function getRadiusEditing(Element $element)
    {
        $config = $element->getConfiguration() + $this->getDefaultConfiguration();
        return $element->getApplication()->getMapEngineCode() !== 'ol2' && \in_array('circle', $config['geometrytypes']);
    }

    public static function updateEntityConfig(Element $entity)
    {
        // Bridge undocumented legacy "paintstyles" to "colors"
        $config = $entity->getConfiguration();
        if (!empty($config['paintstyles']['fillColor'])) {
            $config += array('colors' => array($config['paintstyles']['fillColor']));
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
}

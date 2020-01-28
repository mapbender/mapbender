<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;

/**
 * Mapbender Zoombar
 *
 * The Zoombar element provides a control to pan and zoom, similar to the
 * OpenLayers PanZoomBar control. This element though is easier to use when
 * custom styling is needed.
 *
 * @author Christian Wygoda
 */
class ZoomBar extends Element implements ConfigMigrationInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.zoombar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.zoombar.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.zoombar.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/zoombar.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'target' => null,
            'components' => array("pan", "history", "zoom_box", "zoom_max", "zoom_in_out", "zoom_slider"),
            'anchor' => 'left-top',
            'stepSize' => 50,
            'stepByPixel' => false,
            'draggable' => true);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbZoomBar';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:zoombar.html.twig';
    }

    public static function updateEntityConfig(Entity\Element $entity)
    {
        $defaults = static::getDefaultConfiguration();
        $config = $entity->getConfiguration();
        // Fix dichotomy 'stepSize' (actual backend form field name) vs 'stepsize' (legacy / some YAML applications)
        // Fix dichotomy 'stepByPixel' (actual) vs 'stepbypixel' (legacy / YAML applications)
        if (empty($config['stepSize'])) {
            if (!empty($config['stepsize'])) {
                $config['stepSize'] = $config['stepsize'];
            } else {
                $config['stepSize'] = $defaults['stepSize'];
            }
        }
        if (!isset($config['stepByPixel'])) {
            if (isset($config['stepbypixel'])) {
                $config['stepByPixel'] = $config['stepbypixel'];
            } else {
                $config['stepByPixel'] = $defaults['stepByPixel'];
            }
        }
        // Fix weird mis-treatment of boolean 'stepByPixel' as string (it's a dropdown!)
        if ($config['stepByPixel'] === 'false') {
            $config['stepByPixel'] = false;
        } else {
            // coerce all other values (including string "true") to boolean regularly
            $config['stepByPixel'] = !!$config['stepByPixel'];
        }
        unset($config['stepsize']);
        unset($config['stepbypixel']);
        $entity->setConfiguration($config);
    }

    /**
     * @param Entity\Element $entity
     * @param string[] $componentList
     * @return string[]
     */
    protected static function filterComponentList(Entity\Element $entity, $componentList)
    {
        if (in_array('zoom_slider', $componentList) && !in_array('zoom_in_out', $componentList)) {
            $componentList[] = 'zoom_in_out';
        }
        return $componentList;
    }

    public function getConfiguration()
    {
        $config = $this->entity->getConfiguration();
        $config['components'] = static::filterComponentList($this->entity, $config['components']);
        return $config;
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = $this->getConfiguration();
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(),  array(
            'id' => $this->getId(),
            "title" => $this->getTitle(),
            'configuration' => $configuration,
        ));
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ZoomBarAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:zoombar.html.twig';
    }
}

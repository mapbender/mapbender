<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
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
class ZoomBar extends Element
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
            'components' => array(
                "rotation",
                "history",
                "zoom_max",
                "zoom_in_out",
                "zoom_slider",
            ),
            'anchor' => 'left-top',
            'draggable' => true,
        );
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
        $componentList = array_values(array_diff($componentList, static::getComponentBlacklist($entity)));
        return $componentList;
    }

    protected static function getComponentBlacklist(Entity\Element $entity)
    {
        $blackList = array();
        $application = $entity->getApplication();
        if ($application) {
            switch ($application->getMapEngineCode()) {
                case Entity\Application::MAP_ENGINE_OL2:
                    $blackList[] = 'rotation';
                    $blackList[] = 'history';   // disabled for consistency with OL4
                    break;
                case Entity\Application::MAP_ENGINE_OL4:
                    $blackList[] = 'history';
                    break;
                default:
                    throw new \RuntimeException("Unsupported map engine " . print_r($application->getMapEngineCode(), true));
            }
        }
        return $blackList;
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

<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;

/**
 * @author Paul Schmidt
 */
class ScaleBar extends Element implements ConfigMigrationInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.scalebar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.scalebar.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Scale Bar',
            'target' => null,
            'maxWidth' => 200,
            'anchor' => 'right-bottom',
            'units' => "km",
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbScalebar';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleBarAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:scalebar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $assets = array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scalebar.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/scalebar.scss',
            ),
        );
        if ($this->entity->getApplication()->getMapEngineCode() === Entity\Application::MAP_ENGINE_OL4) {
            $assets['js'][] = '@MapbenderCoreBundle/Resources/public/ol.control.ScaleLinePatched.js';
        }
        return $assets;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:scalebar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render($this->getFrontendTemplatePath(), array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration(),
        ));
    }

    public static function updateEntityConfig(Entity\Element $entity)
    {
        $config = $entity->getConfiguration();
        if (!empty($config['units'])) {
            // demote legacy multi-units array to scalar
            if (\is_array($config['units'])) {
                // use first value
                $vals = \array_values($config['units']);
                $config['units'] = $vals[0];
            }
        } else {
            // Drop falsy / empty array values. Defaults will be used automatically.
            unset($config['units']);
        }
        $entity->setConfiguration($config);
    }
}

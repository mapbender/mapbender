<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 *
 */
class Layertree extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.layertree.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.layertree.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbLayertree';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\LayertreeAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $assets = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/dragdealer.min.js',
                '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.element.layertree.tree.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss',
            ),
            'trans' => array(
                'mb.core.layertree.*',
                'mb.core.metadata.*',
            ),
        );
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $configuration['menu'] = isset($configuration['menu']) ? array_values($configuration['menu']) : array();
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "type" => null,
            "autoOpen" => false,
            "showBaseSource" => true,
            "hideSelect" => false,
            "hideInfo" => false,
            "menu" => array(),
            "useTheme" => false,
            'allowReorder' => true,
            'themes' => array(),
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:layertree.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
            $this->getFrontendTemplatePath(), array(
                'id' => $this->getId(),
                'configuration' => $this->getConfiguration(),
                'title' => $this->getTitle(),
        ));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:layertree.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        if (!empty($configuration['themes'])) {
            foreach ($configuration['themes'] as $k => $themeConfig) {
                $oldLsId = $themeConfig['id'];
                $newLsId = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\Layerset', $oldLsId, true);
                // Must cast to string; entities may return numeric ids during duplication,
                // but all ids loaded by doctrine will be strings.
                $configuration['themes'][$k]['id'] = strval($newLsId);
            }
        }
        return $configuration;
    }
}

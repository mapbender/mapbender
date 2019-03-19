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
    public static function getClassTags()
    {
        return array(
            "mb.core.layertree.tag.layertree",
            "mb.core.layertree.tag.layer",
            "mb.core.layertree.tag.tree");
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
                '@MapbenderCoreBundle/Resources/public/mapbender.metadata.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss',
            ),
            'trans' => array(
                'MapbenderCoreBundle:Element:layertree.json.twig',
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
            "showHeader" => false,
            "hideNotToggleable" => false,
            "hideSelect" => false,
            "hideInfo" => false,
            "menu" => array(),
            "useTheme" => false,
            'themes' => array()
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
        if (isset($configuration['themes'])) {
            for ($i = 0; $i < count($configuration['themes']); $i++) {
                $helpId = intval($configuration['themes'][$i]['id']);
                $id = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\Layerset', $helpId, true);
                $configuration['themes'][$i]['id'] = strval($id);
            }
        }
        return $configuration;
    }
}

<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

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
    public static function listAssets()
    {
        $assets = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/dragdealer.min.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
                '@MapbenderWmsBundle/Resources/public/mapbender.wms.dimension.js',
                'mapbender.element.layertree.tree.js',
                'mapbender.metadata.js'),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/layertree.scss'),
            'trans' => array(
                'MapbenderCoreBundle:Element:layertree.json.twig')
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
            "displaytype" => null,
            "titlemaxlength" => intval(20),
            "autoOpen" => false,
            "showBaseSource" => null,
            "showHeader" => false,
            "hideNotToggleable" => false,
            "hideSelect" => false,
            "hideInfo" => false,
            "menu" => array(),
            "useTheme" => false,
            'themes' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:layertree.html.twig',
            array(
                'id' => $this->getId(),
                'configuration' => $this->getConfiguration(),
                'title' => $this->getTitle()
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:layertree.html.twig';
    }

    /**
     * Changes a element entity configuration while exporting.
     *
     * @param array $formConfiguration element entity configuration
     * @param array $entityConfiguration element entity configuration
     * @return array a configuration
     */
    public function normalizeConfiguration(array $formConfiguration, array $entityConfiguration = array())
    {
        return $formConfiguration;
    }

    /**
     * Changes a element entity configuration while importing.
     *
     * @param array $configuration element entity configuration
     * @param array $idMapper array with ids before denormalize and after denormalize.
     * @return array a configuration
     */
    public function denormalizeConfiguration(array $configuration, array $idMapper = array())
    {
        foreach ($configuration['themes'] as &$theme) {
            if (isset($idMapper['Mapbender\CoreBundle\Entity\Layerset']['map'][intval($theme['id'])])) {
                $theme['id'] = $idMapper['Mapbender\CoreBundle\Entity\Layerset']['map'][intval($theme['id'])];
            }
        }
        return $configuration;
    }
}

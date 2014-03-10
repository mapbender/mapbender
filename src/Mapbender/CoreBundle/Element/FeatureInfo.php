<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Featureinfo element
 *
 * This element will provide feature info for most layer types
 *
 * @author Christian Wygoda
 */
class FeatureInfo extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.core.featureinfo.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.core.featureinfo.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array(
            "mb.core.featureinfo.tag.feature",
            "mb.core.featureinfo.tag.featureinfo",
            "mb.core.featureinfo.tag.info",
            "mb.core.featureinfo.tag.dialog");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Feature Info Dialog',
            'type' => 'dialog',
            "autoOpen" => false,
            "deactivateOnClose" => true,
            "printResult" => false,
            "target" => null);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbFeatureInfo';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\FeatureInfoAdminType';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.featureInfo.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js'),
            'css' => array(),
            'trans' => array('MapbenderCoreBundle:Element:featureinfo.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $configuration = parent::getConfiguration();
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:featureinfo.html.twig',
                    array(
                    'id' => $this->getId(),
                    'configuration' => $configuration,
                    'title' => $this->getTitle()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:featureinfo.html.twig';
    }

}

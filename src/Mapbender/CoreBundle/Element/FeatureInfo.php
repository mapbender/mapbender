<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

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
        return "Feature Info Dialog";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Feature info tool for most layer types";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('dialog', 'featureinfo');
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Feature Info Dialog',
            "autoOpen" => false,
            "deactivateOnClose" => true,
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
    public function getAssets()
    {
        return array(
            'js' => array(
//                'mapbender.element.button.js',
                'mapbender.element.featureInfo.js'
            ),
            'css' => array(
                'mapbender.element.featureInfo.css'
            )
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

}


<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Class POI
 * @package Mapbender\CoreBundle\Element
 */
class POI extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.poi.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.poi.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.poi.tag.poi",
            "mb.core.poi.tag.point",
            "mb.core.poi.tag.interest"
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\POIAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'useMailto' => true,
            'body'      => 'Please take a look at this POI',
            'target'    => null,
            'gps'       => null
        );
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:poi.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js'    => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.poi.js',
                // to call social networks '@MapbenderCoreBundle/Resources/public/mapbender.social_media_connector.js'
            ),
            'css'   => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/poi.scss',
            ),
            'trans' => array(
                'MapbenderCoreBundle:Element:poi.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbPOI';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:poi.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(), array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'configuration' => $this->getConfiguration(),
        ));
    }
}

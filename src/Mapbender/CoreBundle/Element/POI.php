<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * Class POI
 * @package Mapbender\CoreBundle\Element
 */
class POI extends AbstractElementService
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
            /** @todo: use translatable texts */
            'body'      => 'Please take a look at this POI',
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
    public function getRequiredAssets(Element $element)
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
                'mb.core.poi.popup.*',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbPOI';
    }

    /**
     * @inheritdoc
     */
    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:poi.html.twig');
        $view->attributes['class'] = 'mb-element-poi';
        /** @todo: respect configured title! */
        $view->attributes['data-title'] = 'mb.core.poi.sharepoi';   // Used as popup title
        $config = $element->getConfiguration() ?: array();
        $view->variables['body'] = ArrayUtil::getDefault($config, 'body', $this->getDefaultConfiguration()['body']);
        return $view;
    }
}

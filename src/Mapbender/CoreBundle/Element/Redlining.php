<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 *
 */
class Redlining extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.redlining.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.redlining.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getTags()
    {
        return array('mb.core.redlining.tag.redlining', 'mb.core.redlining.tag.geometry');
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbRedlining';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.redlining.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'
            ),
            'css' => array('sass/element/redlining.scss'),
            'trans' => array('MapbenderCoreBundle:Element:redlining.json.twig')
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "displayType" => null,
            "autoActivate" => false,
            "deactivateOnClose" => true,
            "geometryTypes" => array( "point", "line", "polygon", "text")
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\RedliningAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:redlining.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:redlining.html.twig',
            array(
                'id' => $this->getId(),
                'title' => $this->getTitle(),
                'configuration' => $this->getConfiguration()
            )
        );
    }
}

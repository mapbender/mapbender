<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class BaseSourceSwitcherDisplay extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "BaseSourceSwitcherDisplay";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "Changes the url in common with the map or a group of maps.";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.basesourceswitcherdisplay.js'
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/basesourceswitcherdisplay.scss'
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'some element',
            'tooltip' => 'tooltip',
            'target' => null);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbBaseSourceSwitcherDisplay';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseSourceSwitcherDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:basesourceswitcherdisplay.html.twig';
    }

    public function render()
    {
        return $this->container->get('templating')->render(
                'MapbenderCoreBundle:Element:basesourceswitcherdisplay.html.twig',
                array(
                'id' => $this->getId(),
                'configuration' => $this->entity->getConfiguration(),
                'title' => $this->getTitle()));
    }

}

<?php


namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Request;


class ViewManager extends Element
{
    public static function getClassTitle()
    {
        // @todo: translate me
        return 'View manager';
    }

    public static function getClassDescription()
    {
        // @todo: translate me
        return 'Stores and restores map state';
    }

    public function getWidgetName()
    {
        return 'mapbender.mbViewManager';
    }

    public function getFrontendTemplatePath()
    {
        return 'MapbenderCoreBundle:Element:view_manager.html.twig';
    }

    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/element/mbViewManager.js',
            ),
            'css' => array(),
            'trans' => array(),
        );
    }

    public function handleHttpRequest(Request $request)
    {
        /** @var ViewManagerHttpHandler $handler */
        $handler = $this->container->get('mb.element.view_manager.http_handler');
        return $handler->handleHttpRequest($this->entity, $request);
    }
}

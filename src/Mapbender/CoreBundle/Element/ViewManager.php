<?php


namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Request;


class ViewManager extends Element
{
    const ACCESS_READONLY = 'ro';
    const ACCESS_READWRITE = 'rw';
    const ACCESS_READWRITEDELETE = 'rwd';

    public static function getClassTitle()
    {
        return 'mb.core.viewManager.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.viewManager.class.description';
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
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/element/mbViewManager.scss',
            ),
            'trans' => array(
                'mb.core.viewManager.recordStatus.*',
            ),
        );
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ViewManagerAdminType';
    }

    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:view_manager.html.twig';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'publicEntries' => self::ACCESS_READONLY,
            'privateEntries' => true,
            'allowAnonymousSave' => false,
            'showDate' => true,
        );
    }

    public function getFrontendTemplateVars()
    {
        $config = $this->entity->getConfiguration() + $this->getDefaultConfiguration();
        $grants = $this->getHttpHandler()->getGrantsVariables($config);

        return array(
            'grants' => $grants,
        );
    }

    public function handleHttpRequest(Request $request)
    {
        // Extend with defaults
        $this->entity->setConfiguration($this->entity->getConfiguration() + $this->getDefaultConfiguration());
        return $this->getHttpHandler()->handleHttpRequest($this->entity, $request);
    }

    /**
     * @return ViewManagerHttpHandler
     */
    public function getHttpHandler()
    {
        /** @var ViewManagerHttpHandler $handler */
        $handler = $this->container->get('mb.element.view_manager.http_handler');
        return $handler;
    }
}

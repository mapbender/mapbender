<?php


namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;


class ViewManager extends AbstractElementService
{
    const ACCESS_READONLY = 'ro';
    const ACCESS_READWRITE = 'rw';
    const ACCESS_READWRITEDELETE = 'rwd';

    /** @var ViewManagerHttpHandler */
    protected $httpHandler;

    public function __construct(ViewManagerHttpHandler $httpHandler)
    {
        $this->httpHandler = $httpHandler;
    }

    public static function getClassTitle()
    {
        return 'mb.core.viewManager.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.viewManager.class.description';
    }

    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbViewManager';
    }

    public function getRequiredAssets(Element $element)
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
            'showDate' => false,
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:view_manager.html.twig');
        $view->attributes['class'] = 'mb-element-viewmanager';
        $view->variables['grants'] = $this->httpHandler->getGrantsVariables($element->getConfiguration());
        return $view;
    }

    /**
     * @param Element $element
     * @return ViewManagerHttpHandler
     */
    public function getHttpHandler(Element $element)
    {
        return $this->httpHandler;
    }
}

<?php


namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ViewManager extends AbstractElementService
{
    const ACCESS_READONLY = 'ro';
    const ACCESS_READWRITE = 'rw';
    const ACCESS_READWRITEDELETE = 'rwd';

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var ViewManagerHttpHandler */
    protected $httpHandler;

    public function __construct(TokenStorageInterface $tokenStorage,
                                ViewManagerHttpHandler $httpHandler)
    {
        $this->httpHandler = $httpHandler;
        $this->tokenStorage = $tokenStorage;
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
        $token = $this->tokenStorage->getToken();
        $config = $element->getConfiguration() + $this->getDefaultConfiguration();
        if (!$token || ($token instanceof AnonymousToken)) {
            if (empty($config['publicEntries'])) {
                // No access to public entries; private entries undefined for anons
                // => suppress markup entirely
                return false;
            }
        }

        $view = new TemplateView('MapbenderCoreBundle:Element:view_manager.html.twig');
        $view->attributes['class'] = 'mb-element-viewmanager';
        $view->attributes['data-title'] = $element->getTitle() ?: $this->getClassTitle();   // For popup
        $view->variables['grants'] = $this->httpHandler->getGrantsVariables($config);
        $view->variables['showDate'] = $config['showDate'];
        $view->variables['showPublicPrivateState'] = !empty($config['privateEntries']);
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

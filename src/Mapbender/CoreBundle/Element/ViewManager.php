<?php


namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\ViewManagerAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ViewManager extends AbstractElementService
{
    const ACCESS_READONLY = 'ro';
    const ACCESS_READWRITE = 'rw';
    const ACCESS_READWRITEDELETE = 'rwd';

    public function __construct(protected TokenStorageInterface $tokenStorage, protected ViewManagerHttpHandler $httpHandler)
    {
    }

    public static function getClassTitle(): string
    {
        return 'mb.core.viewManager.class.title';
    }

    public static function getClassDescription(): string
    {
        return 'mb.core.viewManager.class.description';
    }

    public function getWidgetName(Element $element): string
    {
        return 'MbViewManager';
    }

    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbViewManager.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/mbViewManager.scss',
            ],
            'trans' => [
                'mb.core.viewManager.recordStatus.*',
            ],
        ];
    }

    public static function getType(): string
    {
        return ViewManagerAdminType::class;
    }

    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/view_manager.html.twig';
    }

    public static function getDefaultConfiguration(): array
    {
        return [
            'publicEntries' => self::ACCESS_READONLY,
            'privateEntries' => true,
            'allowAnonymousSave' => false,
            'showDate' => false,
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    public function getView(Element $element): false|TemplateView
    {
        $token = $this->tokenStorage->getToken();
        $config = $element->getConfiguration() + static::getDefaultConfiguration();
        if (!$token || ($token instanceof NullToken)) {
            if (empty($config['publicEntries'])) {
                // No access to public entries; private entries undefined for anons
                // => suppress markup entirely
                return false;
            }
        }

        $view = new TemplateView('@MapbenderCore/Element/view_manager.html.twig');
        $view->attributes['class'] = 'mb-element-viewmanager';
        $view->attributes['data-title'] = $element->getTitle() ?: static::getClassTitle();   // For popup
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

    public static function getDefaultIcon(): string
    {
        return 'iconBookmark';
    }
}

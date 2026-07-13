<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Element\Type\ShareUrlAdminType;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class ShareUrl extends ButtonLike
{
    public static function getClassTitle(): string
    {
        return 'mb.core.ShareUrl.class.title';
    }

    public static function getClassDescription(): string
    {
        return 'mb.core.ShareUrl.class.description';
    }

    public function getWidgetName(Element $element): string
    {
        return 'MbShareUrl';
    }

    public static function getType(): string
    {
        return ShareUrlAdminType::class;
    }

    /**
     * @inheritdoc
     * @return mixed[]
     */
    public function getRequiredAssets(Element $element): array
    {
        $required = parent::getRequiredAssets($element) + [
            'js' => [],
            'css' => [],
            'trans' => [],
        ];
        // Remove / replace base button script
        $required['js'] = array_merge($required['js'], [
            '@MapbenderCoreBundle/Resources/public/elements/MbShareUrl.js',
        ]);
        $required['css'] = array_merge($required['css'], [
            '@MapbenderCoreBundle/Resources/public/sass/element/mbShareUrl.scss',
        ]);
        $required['trans'] = array_merge($required['trans'], [
            'mb.core.ShareUrl.*',
        ]);
        return $required;
    }

    public static function getDefaultConfiguration()
    {
        $defaults = parent::getDefaultConfiguration();
        $defaults['element_icon'] = self::getDefaultIcon();
        // icon is hard-coded (see twig template)
        unset($defaults['icon']);
        return $defaults;
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/ShareUrl.html.twig');
        parent::initializeView($view, $element);
        $view->attributes['class'] = 'mb-button mb-element-shareurl';
        return $view;
    }

    public static function getDefaultIcon(): string
    {
        return 'iconShare';
    }
}

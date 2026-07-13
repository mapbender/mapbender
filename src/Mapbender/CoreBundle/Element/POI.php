<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\POIAdminType;
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
    public static function getClassTitle(): string
    {
        return "mb.core.poi.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.poi.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return POIAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'useMailto' => true,
            'body'      => 'mb.core.poi.admin.placeholder',
            'gps'       => null,
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/poi.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js'    => [
                '@MapbenderCoreBundle/Resources/public/elements/MbPoi.js',
                // to call social networks '@MapbenderCoreBundle/Resources/public/mapbender.social_media_connector.js'
            ],
            'css'   => [
                '@MapbenderCoreBundle/Resources/public/sass/element/poi.scss',
            ],
            'trans' => [
                'mb.core.poi.popup.*',
                'mb.core.poi.accept'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbPoi';
    }

    /**
     * @inheritdoc
     */
    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/poi.html.twig');
        $view->attributes['class'] = 'mb-element-poi';
        $view->attributes['data-title'] = 'mb.core.poi.sharepoi';   // Used as popup title
        $config = $element->getConfiguration() ?: [];
        $view->variables['body'] = ArrayUtil::getDefault($config, 'body', static::getDefaultConfiguration()['body']);
        return $view;
    }

    public static function getDefaultIcon(): string
    {
        return 'iconPoi';
    }
}

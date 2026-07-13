<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\ActivityIndicatorAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Utils\HtmlUtil;

/**
 * Activity indicator
 *
 * @author Christian Wygoda
 */
class ActivityIndicator extends AbstractElementService
{

    /**
     * @inheritdoc
     */
    static function getClassTitle(): string
    {
        return "mb.core.activityindicator.class.title";
    }

    /**
     * @inheritdoc
     */
    static function getClassDescription(): string
    {
        return "mb.core.activityindicator.class.description";
    }

    /**
     * @inheritdoc
     */
    static function getDefaultConfiguration(): array
    {
        return [
            'tooltip' => static::getClassTitle(),
            'activityClass' => 'mb-activity',
            'ajaxActivityClass' => 'mb-activity-ajax',
            'tileActivityClass' => 'mb-activity-tile',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbActivityIndicator';
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return ActivityIndicatorAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbActivityIndicator.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/activityindicator.scss',
            ],
        ];
    }

    public function getView(Element $element): StaticView
    {
        $view = new StaticView(HtmlUtil::renderTag('i', '', [
            'class' => 'fa fas fa-spinner fa-spin activityindicator-spinner',
        ]));
        $view->attributes['class'] = 'mb-element-activityindicator';
        $view->attributes['title'] = $element->getConfiguration()['tooltip'] ?: $element->getTitle() ?: static::getClassTitle();
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/activityindicator.html.twig';
    }

}

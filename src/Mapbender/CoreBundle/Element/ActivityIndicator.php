<?php
namespace Mapbender\CoreBundle\Element;

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
    static function getClassTitle()
    {
        return "mb.core.activityindicator.class.title";
    }

    /**
     * @inheritdoc
     */
    static function getClassDescription()
    {
        return "mb.core.activityindicator.class.description";
    }

    /**
     * @inheritdoc
     */
    static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => static::getClassTitle(),
            'activityClass' => 'mb-activity',
            'ajaxActivityClass' => 'mb-activity-ajax',
            'tileActivityClass' => 'mb-activity-tile',
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbActivityIndicator';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ActivityIndicatorAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.activityindicator.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/activityindicator.scss',
            ),
        );
    }

    public function getView(Element $element)
    {
        $view = new StaticView(HtmlUtil::renderTag('i', '', array(
            'class' => 'fa fas fa-spinner fa-spin',
        )));
        $view->attributes['class'] = 'mb-element-activityindicator';
        $view->attributes['title'] = $element->getConfiguration()['tooltip'] ?: $element->getTitle() ?: $this->getClassTitle();
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:activityindicator.html.twig';
    }

}

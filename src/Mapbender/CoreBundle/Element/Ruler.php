<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Entity\Element;


class Ruler extends AbstractElementService
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.ruler.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.ruler.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbRuler.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/ruler.scss',
            ],
            'trans' => [
                'mb.core.ruler.create_error',
                'mb.core.ruler.help',
                'mb.core.ruler.tag.line',
                'mb.core.ruler.tag.area',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\RulerAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderManager/Element/ruler.html.twig';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'type' => 'line',
            'help' => 'mb.core.ruler.help',
            'precision' => 'auto',
            'fillColor' => 'rgba(255,255,255,0.2)',
            'strokeColor' => '#3399CC',
            'strokeWidth' => 2,
            'strokeWidthWhileDrawing' => 3,
            'fontColor' => '#000000',
            'fontSize' => 12,
            'element_icon' => self::getDefaultIcon(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbRuler';
    }

    public function getView(Element $element)
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-ruler';
        $view->attributes['data-title'] = $element->getTitle();
        $view->attributes['data-test'] = 'mb-ruler-test';
        return $view;
    }

    public static function getDefaultIcon()
    {
        return 'icon-line-ruler';
    }
}

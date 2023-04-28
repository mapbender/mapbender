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
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.ruler.js',
            ),
            'css' => array(),
            'trans' => array(
                'mb.core.ruler.create_error',
                'mb.core.ruler.help',
            ),
        );
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
        return 'MapbenderManagerBundle:Element:ruler.html.twig';
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
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbRuler';
    }

    public function getView(Element $element)
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-ruler';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }
}

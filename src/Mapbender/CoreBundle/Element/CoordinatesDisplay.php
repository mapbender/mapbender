<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;

/**
 * Coordinates display
 *
 * Displays the mouse coordinates
 *
 * @author Paul Schmidt
 * @author Christian Wygoda
 */
class CoordinatesDisplay extends AbstractElementService implements FloatableElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.coordinatesdisplay.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.coordinatesdisplay.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CoordinatesDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.coordinatesdisplay.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/coordinatesdisplay.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'anchor' => 'right-bottom',
            'label' => false,
            'numDigits' => 2,
            'empty' => 'x= - y= -',
            'prefix' => 'x= ',
            'separator' => ' y= ',
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbCoordinatesDisplay';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:coordinatesdisplay.html.twig');
        $view->attributes['class'] = 'mb-element-coordsdisplay';
        $config = $element->getConfiguration();
        $view->variables['label'] = $config['label']
            ? ($element->getTitle() ?: $this->getClassTitle())
            : false
        ;
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:coordinatesdisplay.html.twig';
    }

}

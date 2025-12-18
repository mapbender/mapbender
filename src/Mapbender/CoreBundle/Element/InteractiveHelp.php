<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class InteractiveHelp extends AbstractElementService
{
    public static function getClassTitle()
    {
        return 'mb.interactivehelp.element.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.interactivehelp.element.class.description';
    }

    public function getWidgetName(Element $element)
    {
        return 'MbInteractiveHelp';
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbInteractiveHelp.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/interactivehelp.scss',
            ),
       );
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'autoOpen' => false,
            'helptexts' => array(
                'intro' => array(
                    'title' => 'mb.core.interactivehelp.intro.title',
                    'description' => 'mb.core.interactivehelp.intro.description',
                ),
                'chapters' => array(
                    array(
                        'title' => 'mb.core.interactivehelp.wmsloader.title',
                        'description' => 'mb.core.interactivehelp.wmsloader.description',
                        'element' => array(
                            'id' => '',
                            'title' => 'mb.core.interactivehelp.wmsloader.title',
                            'type' => 'Mapbender\WmsBundle\Element\WmsLoader',
                        ),
                        'selector' => 'mb-element-wmsloader',
                    ),
                    array(
                        'title' => 'mb.core.interactivehelp.sketch.title',
                        'description' => 'mb.core.interactivehelp.sketch.description',
                        'element' => array(
                            'id' => '',
                            'title' => 'mb.core.interactivehelp.sketch.title',
                            'type' => 'Mapbender\CoreBundle\Element\Sketch',
                        ),
                        'selector' => 'mb-element-sketch',
                    ),
                ),
            ),
        );
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\InteractiveHelpAdminType';
    }

    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/interactivehelp.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderCore/Element/interactivehelp.html.twig');
        $view->attributes['class'] = 'mb-element-interactivehelp';
        return $view;
    }
}

<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Sketch Element
 * 
 * @author Paul Schmidt
 */
class Sketch extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.sketch.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.sketch.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.sketch.tag.sketch",
            "mb.core.sketch.tag.circle",
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SketchAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.sketch.js',
            ),
            'css' => array(),
            'trans' => array(
                'MapbenderCoreBundle:Element:sketch.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "Sketch",
            "target" => null,
            "defaultType" => null,
            "types" => null,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbSketch';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:sketch.html.twig';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:sketch.html.twig';
    }
}

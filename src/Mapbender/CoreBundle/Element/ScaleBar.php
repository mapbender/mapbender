<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class ScaleBar extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.scalebar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.scalebar.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.scalebar.tag.scale",
            "mb.core.scalebar.tag.bar",
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Scale Bar',
            'tooltip' => 'Scale Bar',
            'target' => null,
            'maxWidth' => 200,
            'anchor' => 'right-bottom',
            'units' => array(
                "km",
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbScalebar';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleBarAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:scalebar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scalebar.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/scalebar.scss',
            ),
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:scalebar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render($this->getFrontendTemplatePath(), array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration(),
        ));
    }

}

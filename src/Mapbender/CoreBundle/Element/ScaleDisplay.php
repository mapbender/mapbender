<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class ScaleDisplay extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.scaledisplay.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.scaledisplay.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.scaledisplay.tag.scale",
            "mb.core.scaledisplay.tag.display",
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Scale Display',
            'tooltip' => 'Scale Display',
            'target' => null,
            'unitPrefix' => false,
            'scalePrefix' => 'Scale = ',
            'anchor' => 'right-bottom',
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbScaledisplay';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:scaledisplay.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scaledisplay.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/scaledisplay.scss',
            ),
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:scaledisplay.html.twig';
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

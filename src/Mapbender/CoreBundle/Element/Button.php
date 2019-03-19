<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Button element
 *
 * @author Christian Wygoda
 */
class Button extends Element
{
    /**
     * @inheritdoc
     */
    public static $ext_api = false;

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.button.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.button.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array("mb.core.button.tag.button");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'button',
            'tooltip' => 'button',
            'label' => true,
            'icon' => null,
            'target' => null,
            'click' => null,
            'group' => null,
            'action' => null,
            'deactivate' => null,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        if (get_class() === get_called_class()) {
            // Most definitely a control button
            return 'mapbender.mbControlButton';
        } else {
            // Called for a child class without an own getWidgetName implementation

            // We expect any other Element inheriting from button to just want a simple
            // self-highlighting toggle switch, with no actual desire to control other
            // Elements. We give them a simpler, non-controlling button widget.
            @trigger_error(
                "Warning: assuming " . get_class($this) . " doesn't want to control other Element widgets,"
              . " using different JS widget constructor. Override getWidgetName if you really want to control"
              . " other Elements", E_USER_DEPRECATED);
            return 'mapbender.mbButton';
        }
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ButtonAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
                '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/button.scss',
            ),
        );
    }

    public function getConfiguration()
    {
        $config = $this->entity->getConfiguration();
        if (!empty($config['click']) && 0 === strpos($config['click'], '#')) {
            return array_replace($config, array(
                'click' => null,
            ));
        } else {
            return $config;
        }
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:button{$suffix}";
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:button.html.twig';
    }

}

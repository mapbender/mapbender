<?php
namespace Mapbender\CoreBundle\Element;

/**
 * Button element
 *
 * @author Christian Wygoda
 * @internal
 * Unified LinkButton and / or ControlButton remnant. Can no longer be added to applications.
 * Kept only to support project-level child classes.
 */
class Button extends BaseButton
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
    public static function getDefaultConfiguration()
    {
        return array_replace(parent::getDefaultConfiguration(), array(
            'target' => null,
            'click' => null,
            'group' => null,
            'action' => null,
            'deactivate' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbButton';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ButtonAdminType';
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:button{$suffix}";
    }
}

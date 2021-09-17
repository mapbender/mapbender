<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;

/**
 * Button element
 *
 * @author Christian Wygoda
 * @internal
 * Unified LinkButton and / or ControlButton remnant. Can no longer be added to applications.
 * Kept only to support project-level child classes.
 */
class Button extends BaseButton implements ConfigMigrationInterface
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

    public static function updateEntityConfig(Entity\Element $entity)
    {
        if ($entity->getClass() && $entity->getClass() === get_called_class()) {
            $config = $entity->getConfiguration();
            if (!empty($config['click'])) {
                $entity->setClass('Mapbender\CoreBundle\Element\LinkButton');
            } else {
                $entity->setClass('Mapbender\CoreBundle\Element\ControlButton');
            }
        }
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

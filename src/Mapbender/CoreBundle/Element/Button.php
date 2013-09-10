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
    static public function getClassTitle()
    {
        return "Button";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('Button');
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
            'deactivate' => null);
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

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.button.js','@FOMCoreBundle/Resources/public/js/widgets/checkbox.js'),
            'css' => array());
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderCoreBundle:Element:button.html.twig',
                                 array(
                            'id' => $this->getId(),
                            'title' => $this->getTitle(),
                            'configuration' => $this->entity->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:button.html.twig';
    }
}


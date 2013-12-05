<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class BaseSourceSwitcher extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "BaseSourceSwitcher";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "BaseSourceSwitcher";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('basesourceswitcher', "base", "source", "switcher");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => "BaseSourceSwitcher",
            'target' => null,
//            'anchor' => 'right-top',
//            'position' => array('0px', '0px'),
            'sourcesets' => array(
                array("title" => 'Hintergrund', "sources" => array(), "show" => true),
                array("title" => 'Hintergrund S/W', "sources" => array(), "show" => true),
                array("title" => 'Luftbilder', "sources" => array(), "show" => true),
                array("title" => 'Kein Hintergrund', "sources" => array(), "show" => true)
            ),
//            'fullscreen' => false
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbBaseSourceSwitcher';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseSourceSwitcherAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCorBundle:ElementAdmin:basesourceswitcher.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.basesourceswitcher.js')
//            'css' => array('mapbender.element.basesourceswitcher.css')
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderCorBundle:Element:basesourceswitcher.html.twig',
                    array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
    }

}

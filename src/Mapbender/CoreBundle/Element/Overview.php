<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class Overview extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.core.overview.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.core.overview.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array(
            "mb.core.overview.tag.overview",
            "mb.core.overview.tag.map");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Overview',
            'tooltip' => "Overview",
            'layerset' => null,
            'target' => null,
            'width' => 200,
            'height' => 100,
            'anchor' => 'right-top',
            'position' => array('0px', '0px'),
            'maximized' => true,
            'fixed' => true);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbOverview';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\OverviewAdminType';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'js' => array('mapbender.element.overview.js'),
            //TODO: Split up
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/overview.scss'));
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:overview.html.twig',
                    array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:overview.html.twig';
    }

}

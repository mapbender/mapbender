<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;

class HTMLElement extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.htmlelement.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.htmlelement.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.htmlelement.tag.html");
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array(
            'js' => array('mapbender.element.htmlelement.js'),
            'css' => array('sass/element/htmlelement.scss')
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\HTMLElementAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'classes' => 'html-element-inline'
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbHTMLElement';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:htmlelement.html.twig',
                    array(
                    'id'            => $this->getId(),
                    'entity'        => $this->entity,
                    'application'   => $this->application,
                    'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:htmlelement.html.twig';
    }

    static public function getFormAssets()
    {
        return array(
            'js' => array(
                'bundles/mapbendermanager/codemirror/lib/codemirror.js',
                'bundles/mapbendermanager/codemirror/mode/xml/xml.js',
                'bundles/mapbendermanager/codemirror/keymap/sublime.js',
                'bundles/mapbendermanager/codemirror/addon/selection/active-line.js',
                'bundles/mapbendercore/mapbender.admin.htmlelement.js',
            ),
            'css' => array(
                'bundles/mapbendermanager/codemirror/lib/codemirror.css',
                'bundles/mapbendermanager/codemirror/theme/neo.css',
            )
        );
    }

}

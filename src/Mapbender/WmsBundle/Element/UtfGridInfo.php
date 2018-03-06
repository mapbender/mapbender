<?php


namespace Mapbender\WmsBundle\Element;


use Mapbender\CoreBundle\Component\Element;

class UtfGridInfo extends Element
{
    /**
     * Extended API. The ext_api defines, if an element can be used as a target
     * element.
     * @var boolean extended api
     */
    public static $ext_api = false;

    /**
     * Returns the element class title
     *
     * This is primarily used in the manager backend when a list of available
     * elements is given.
     *
     * @return string
     */
    public static function getClassTitle()
    {
        return "mb.wms.utfgridinfo.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.wms.utfgridinfo.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbUtfGridInfo';
    }

    public static function getDefaultConfiguration()
    {
        return parent::getDefaultConfiguration() + array(
            'labelFormats' => null,
        );
    }

    public function getAssets()
    {
        $assets = array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.base.js',
                'mapbender.element.utfgriddisplay.js',
            ),
            'css' => array(
                '@MapbenderWmsBundle/Resources/public/sass/element/utfgriddisplay.scss',
            ),
            'trans' => array('MapbenderWmsBundle:Element:utfgriddisplay.json.twig')
        );
    }

    public function getFrontendTemplateVars()
    {
        return parent::getFrontendTemplateVars() + array(
            'debug' => true,
        );
    }
}

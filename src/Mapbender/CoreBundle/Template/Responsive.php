<?php
namespace Mapbender\CoreBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Responsive
 *
 * @author Vadim Hermann
 */
class Responsive extends Template
{
    /**
     * @inheritdoc
     */
    public static function getRegionsProperties()
    {
        return array(
            'sidepane' => array(
                'tabs' => array(
                    'state' => true
                )
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getTitle()
    {
        return 'Responsive';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $assets = array(
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/theme/mapbender3.scss',
                           '@MapbenderCoreBundle/Resources/public/sass/template/responsive.scss'),
            'js' => array('@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                          '@MapbenderCoreBundle/Resources/public/js/responsive.js'),
            'trans' => array()
        );
        return $assets;
    }

    /**
     * @inheritdoc
     */
    public function getAssets($type)
    {
        $assets = $this::listAssets();
        return $assets[$type];
    }

    /**
     * @inheritdoc
     */
    public static function getRegions()
    {
        return array('toolbar', 'content', 'infocontainer');
    }

    /**
     * @inheritdoc
     */
    public static function getElementWhitelist(){
        return array('toolbar'       => array('Mapbender\CoreBundle\Element\Button',
                                              'Mapbender\CoreBundle\Element\AboutDialog'),
                     'content'       => array(),
                     'infocontainer' => array());
    }

    /**
     * @inheritdoc
     */
    public function render($format = 'html', $html = true, $css = true,
        $js = true)
    {
        $region_props = $this->application->getEntity()->getNamedRegionProperties();
        $default_region_props = $this->getRegionsProperties();

        $templating = $this->container->get('templating');
        return $templating
                ->render('MapbenderCoreBundle:Template:responsive.html.twig',
                    array(
                    'html' => $html,
                    'css' => $css,
                    'js' => $js,
                    'application' => $this->application,
                    'region_props' => $region_props,
                    'default_region_props' => $default_region_props));
    }

}

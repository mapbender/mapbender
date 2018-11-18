<?php
namespace Mapbender\MobileBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Mobile Template
 *
 */
class Mobile extends Template
{
    /** @var string Application title */
    protected static $title = 'Mapbender Mobile template';

    /** @var array Late assets */
    protected $lateAssets = array(
        'js'    => array(),
        'css'   => array(
            '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss'
        ),
        'trans' => array(),
    );

    protected static $js  = array(
        '/components/underscore/underscore-min.js',
        '@MapbenderMobileBundle/Resources/public/js/mapbender.mobile.js',
        '@MapbenderMobileBundle/Resources/public/js/vendors/jquery.mobile.custom.min.js',
        '@MapbenderMobileBundle/Resources/public/js/mobile.js',
        '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js'
    );

    /**  @var array Region names */
    protected static $regions = array('footer', 'content', 'mobilePane');

    public function getTwigTemplate()
    {
        return 'MapbenderMobileBundle:Template:mobile.html.twig';
    }
}

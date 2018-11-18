<?php
namespace Mapbender\MobileBundle\Template;

use Mapbender\CoreBundle\Component\Template;

/**
 * Template Mobile Template
 *
 */
class Mobile extends Template
{
    public static function getTitle()
    {
        return 'Mapbender Mobile template';
    }

    public static function getRegions()
    {
        return array(
            'footer',
            'content',
            'mobilePane',
        );
    }

    public function getLateAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss',
                );
            default:
                return parent::getLateAssets($type);
        }
    }

    public function getAssets($type)
    {
        switch ($type) {
            case 'js':
                return array(
                    '/components/underscore/underscore-min.js',
                    '@MapbenderMobileBundle/Resources/public/js/mapbender.mobile.js',
                    '@MapbenderMobileBundle/Resources/public/js/vendors/jquery.mobile.custom.min.js',
                    '@MapbenderMobileBundle/Resources/public/js/mobile.js',
                    '@MapbenderCoreBundle/Resources/public/regional/vendor/notify.0.3.2.min.js',
                );
            default:
                return parent::getAssets($type);
        }
    }

    public function getTwigTemplate()
    {
        return 'MapbenderMobileBundle:Template:mobile.html.twig';
    }
}

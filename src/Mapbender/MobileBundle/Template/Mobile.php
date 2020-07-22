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

    public function getAssets($type)
    {
        switch ($type) {
            case 'css':
                return array(
                    '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss',
                );
            case 'js':
                return array(
                    '@MapbenderMobileBundle/Resources/public/js/mapbender.mobile.js',
                    '@MapbenderMobileBundle/Resources/public/js/vendors/jquery.mobile.custom.min.js',
                    '@MapbenderMobileBundle/Resources/public/js/mobile.js',
                );
            default:
                return parent::getAssets($type);
        }
    }

    public function getTwigTemplate()
    {
        return 'MapbenderMobileBundle:Template:mobile.html.twig';
    }

    public function getBodyClass(\Mapbender\CoreBundle\Entity\Application $application)
    {
        return 'mobile-template';
    }
}

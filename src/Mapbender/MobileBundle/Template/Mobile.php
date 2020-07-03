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
            case 'css':
                return array(
                    '@MapbenderMobileBundle/Resources/public/sass/theme/mobile_skeleton.scss',
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

    public function getVariablesAssets()
    {
        return array_merge(parent::getVariablesAssets(), array(
            '@MapbenderMobileBundle/Resources/public/sass/theme/variables.scss',
        ));
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

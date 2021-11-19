<?php
namespace Mapbender\MobileBundle\Template;

use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;

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

    public function getSassVariablesAssets(Application $application)
    {
        return array(
            '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
            '@MapbenderMobileBundle/Resources/public/sass/theme/variables.scss',
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

    public function getBodyClass(Application $application)
    {
        return 'mobile-template';
    }

    public static function getRegionSettingsFormType($regionName)
    {
        switch ($regionName) {
            case 'footer':
                return 'Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType';
            default:
                return null;
        }
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        switch ($regionName) {
            case 'footer':
                return array(
                    'item_alignment' => 'center',
                    'generate_button_menu' => false,
                );
            default:
                return parent::getRegionPropertiesDefaults($regionName);
        }
    }
}

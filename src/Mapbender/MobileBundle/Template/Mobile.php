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

    public function getTemplateVars(Application $application)
    {
        $vars = parent::getTemplateVars($application);
        return $vars + array(
            'toolbar_alignment_class' => $this->getToolbarAlignmentClass($application, 'footer')
        );
    }

    public function getBodyClass(Application $application)
    {
        return 'mobile-template';
    }

    public static function getRegionSettingsFormType($regionName)
    {
        switch ($regionName) {
            case 'footer':
                return 'Mapbender\MobileBundle\Form\Type\Template\FooterType';
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

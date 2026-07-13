<?php
namespace Mapbender\MobileBundle\Template;

use Mapbender\CoreBundle\Form\Type\Template\BaseToolbarType;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;

/**
 * Template Mobile Template
 *
 */
class Mobile extends Template
{
    public static function getTitle(): string
    {
        return 'Mapbender Mobile template';
    }

    public static function getRegions(): array
    {
        return [
            'footer',
            'content',
            'mobilePane',
        ];
    }

    public function getSassVariablesAssets(Application $application): array
    {
        return [
            '@MapbenderCoreBundle/Resources/public/sass/libs/_variables.scss',
            '@MapbenderMobileBundle/Resources/public/sass/theme/variables.scss',
        ];
    }

    public function getAssets($type)
    {
        return match ($type) {
            'css' => [
                '@MapbenderMobileBundle/Resources/public/sass/theme/mobile.scss',
            ],
            'js' => [
                '@MapbenderMobileBundle/Resources/public/js/mapbender.mobile.js',
                '@MapbenderMobileBundle/Resources/public/js/mobile.js',
            ],
            default => parent::getAssets($type),
        };
    }

    public function getTwigTemplate(): string
    {
        return '@MapbenderMobile/Template/mobile.html.twig';
    }

    public function getBodyClass(Application $application): string
    {
        return 'mobile-template';
    }

    public static function getRegionSettingsFormType($regionName): ?string
    {
        return match ($regionName) {
            'footer' => BaseToolbarType::class,
            default => null,
        };
    }

    public static function getRegionPropertiesDefaults($regionName)
    {
        return match ($regionName) {
            'footer' => [
                'item_alignment' => 'center',
                'generate_button_menu' => false,
            ],
            default => parent::getRegionPropertiesDefaults($regionName),
        };
    }
}
